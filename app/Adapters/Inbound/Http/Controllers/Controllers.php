<?php

namespace App\Adapters\Inbound\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Adapters\Inbound\Http\Responses\StandardApiResponse;
use App\Application\Services\LoginService;
use App\Application\Services\CreateLibrarianService;
use App\Application\Services\CategoryService;
use App\Application\Services\CreateBookService;
use App\Application\Services\SearchBooksService;
use App\Application\Services\MemberService;
use App\Application\Services\LendBooksService;
use App\Application\Services\ReturnItemService;
use App\Application\Services\OverdueService;
use App\Application\Services\MostBorrowedReportService;
use App\Application\DTOs\CreateBookCommand;
use App\Application\DTOs\UpdateBookCommand;
use App\Application\DTOs\BookQuery;
use App\Application\DTOs\CreateMemberCommand;
use App\Application\DTOs\UpdateMemberCommand;
use App\Application\DTOs\LendBooksCommand;
use App\Domain\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\DB;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}

class AuthController
{
    public function login(Request $request, LoginService $service): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $result = $service->login($validated['username'], $validated['password']);
            return response()->json([
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'] ?? 'Bearer',
                'expires_in' => $result['expires_in'] ?? 28800,
                'role' => $result['role'],
                'token' => $result['token'] ?? $result['access_token'],
                'user' => $result['user']
            ]);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }
}

class UserController
{
    public function store(Request $request, CreateLibrarianService $service): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:120',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:librarian',
        ]);

        try {
            $user = $service->create($validated['username'], $validated['password'], 'librarian');
            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ], 201);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }
}

class CategoryController
{
    public function index(CategoryService $service): JsonResponse
    {
        return response()->json($service->getCategories());
    }
}

class BookController
{
    public function index(Request $request, SearchBooksService $service): JsonResponse
    {
        $page = max(1, (int)$request->query('page', 1));
        $size = min(50, max(1, (int)$request->query('size', 20)));
        $offset = ($page - 1) * $size;

        $query = new BookQuery(
            search: $request->query('q') ?? $request->query('search'),
            categoryId: $request->query('category_id'),
            availableOnly: $request->boolean('available_only'),
            limit: $size,
            offset: $offset,
            page: $page
        );

        $items = $service->search($query);
        $total = \App\Adapters\Outbound\Persistence\Models\BookModel::count();

        // If direct array requested by client/frontend
        if ($request->header('Accept-Style') === 'flat') {
            return response()->json($items);
        }

        return response()->json([
            'items' => $items,
            'page' => $page,
            'size' => $size,
            'total' => $total
        ]);
    }

    public function show(string $id, SearchBooksService $service): JsonResponse
    {
        try {
            $book = $service->findById($id);
            return response()->json([
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'category' => ['id' => $book->categoryId, 'name' => $book->categoryName ?? 'General'],
                'total_copies' => $book->totalCopies,
                'available_copies' => $book->availableCopies,
                'cover_key' => $book->coverKey
            ]);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function store(Request $request, CreateBookService $service): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:250',
            'author' => 'required|string|max:200',
            'isbn' => 'required|string|size:13',
            'category_id' => 'required|string',
            'total_copies' => 'required|integer|min:1',
            'cover' => 'nullable|file|image|max:2048',
        ]);

        try {
            $cmd = new CreateBookCommand(
                $validated['title'],
                $validated['author'],
                $validated['isbn'],
                $validated['category_id'],
                (int)$validated['total_copies'],
                $request->file('cover')
            );
            $book = $service->create($cmd);
            return response()->json($book, 201);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function update(Request $request, string $id, SearchBooksService $service): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:250',
            'author' => 'required|string|max:200',
            'category_id' => 'required|string',
            'total_copies' => 'required|integer|min:1',
            'cover' => 'nullable|file|image|max:2048',
        ]);

        try {
            $cmd = new UpdateBookCommand(
                $validated['title'],
                $validated['author'],
                $validated['category_id'],
                (int)$validated['total_copies'],
                $request->file('cover')
            );
            $book = $service->update($id, $cmd);
            return response()->json($book);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function updateCover(Request $request, string $id, SearchBooksService $service, \App\Application\Ports\Outbound\FileStorageInterface $storage): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|file|image|max:2048',
            'cover' => 'nullable|file|image|max:2048',
        ]);

        $file = $request->file('file') ?? $request->file('cover');
        if (!$file) {
            return StandardApiResponse::error('VALIDATION_ERROR', 'Se requiere archivo de portada', [], 422);
        }

        try {
            $book = $service->findById($id);
            $url = $storage->store($file, 'covers');
            
            $repo = app(\App\Application\Ports\Outbound\BookRepositoryInterface::class);
            $b = $repo->findById($id);
            if ($b) {
                $b->attachCover($url);
                $repo->save($b);
            }
            return response()->json(['cover_key' => $url, 'cover_url' => $url, 'message' => 'Portada actualizada']);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function destroy(string $id, SearchBooksService $service): JsonResponse
    {
        try {
            $service->delete($id);
            return response()->json(null, 204);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }
}

class MemberController
{
    public function index(Request $request, MemberService $service): JsonResponse
    {
        $page = max(1, (int)$request->query('page', 1));
        $size = min(50, max(1, (int)$request->query('size', 20)));

        $filters = array_filter([
            'q' => $request->query('q') ?? $request->query('search'),
        ]);

        $all = $service->list($filters);
        $total = count($all);
        $sliced = array_slice($all, ($page - 1) * $size, $size);

        if ($request->header('Accept-Style') === 'flat') {
            return response()->json($all);
        }

        return response()->json([
            'items' => $sliced,
            'page' => $page,
            'size' => $size,
            'total' => $total
        ]);
    }

    public function show(string $id, MemberService $service): JsonResponse
    {
        try {
            $member = $service->findById($id);
            return response()->json([
                'id' => $member->id,
                'full_name' => $member->fullName,
                'document_number' => $member->documentNumber,
                'active_loans' => $member->activeLoans,
                // UI aliases
                'name' => $member->fullName,
                'document_type' => $member->documentType,
                'email' => $member->email,
                'phone' => $member->phone
            ]);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function store(Request $request, MemberService $service): JsonResponse
    {
        $fullName = $request->input('full_name') ?? $request->input('name');
        $doc = $request->input('document_number');

        if (!$fullName || trim($fullName) === '' || !$doc || trim($doc) === '') {
            return StandardApiResponse::error('VALIDATION_ERROR', 'full_name y document_number son obligatorios', [], 422);
        }

        try {
            $cmd = new CreateMemberCommand(
                trim($fullName),
                trim($doc),
                $request->input('document_type', 'CC'),
                $request->input('email', ''),
                $request->input('phone', '')
            );
            $res = $service->create($cmd);
            return response()->json([
                'id' => $res->id,
                'full_name' => $res->fullName,
                'document_number' => $res->documentNumber,
                'active_loans' => 0,
                'name' => $res->fullName
            ], 201);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function update(Request $request, string $id, MemberService $service): JsonResponse
    {
        $fullName = $request->input('full_name') ?? $request->input('name');
        if (!$fullName) {
            return StandardApiResponse::error('VALIDATION_ERROR', 'full_name es obligatorio', [], 422);
        }

        try {
            $cmd = new UpdateMemberCommand(
                trim($fullName),
                $request->input('email', ''),
                $request->input('phone', '')
            );
            return response()->json($service->update($id, $cmd));
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }
}

class LoanController
{
    public function index(Request $request, \App\Application\Ports\Outbound\LoanRepositoryInterface $loanRepo): JsonResponse
    {
        $page = max(1, (int)$request->query('page', 1));
        $size = min(50, max(1, (int)$request->query('size', 20)));

        $filters = array_filter([
            'status' => $request->query('status'),
            'member_id' => $request->query('member_id'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);

        $loans = $loanRepo->findAll($filters);
        $dtos = array_map(fn($l) => \App\Application\DTOs\LoanDTO::fromEntity($l), $loans);
        $total = count($dtos);
        $sliced = array_slice($dtos, ($page - 1) * $size, $size);

        if ($request->header('Accept-Style') === 'flat') {
            return response()->json($dtos);
        }

        return response()->json([
            'items' => $sliced,
            'page' => $page,
            'size' => $size,
            'total' => $total
        ]);
    }

    public function overdue(OverdueService $service): JsonResponse
    {
        return response()->json([
            'items' => $service->getOverdueItems()
        ]);
    }

    public function show(string $id, \App\Application\Ports\Outbound\LoanRepositoryInterface $loanRepo): JsonResponse
    {
        $loan = $loanRepo->findById($id);
        if (!$loan) {
            return StandardApiResponse::error('NOT_FOUND', 'Préstamo no encontrado', [], 404);
        }
        return response()->json(\App\Application\DTOs\LoanDTO::fromEntity($loan));
    }

    public function store(Request $request, LendBooksService $service): JsonResponse
    {
        $memberId = $request->input('member_id');
        $rawItems = $request->input('items');
        $rawBookIds = $request->input('book_ids');

        $bookIds = [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $it) {
                if (isset($it['book_id'])) $bookIds[] = (string)$it['book_id'];
            }
        } elseif (is_array($rawBookIds)) {
            $bookIds = array_map('strval', $rawBookIds);
        }

        if (!$memberId || empty($bookIds)) {
            return StandardApiResponse::error('VALIDATION_ERROR', 'member_id y array de libros son obligatorios', [], 422);
        }

        $currentUser = $request->get('auth_user') ?? $request->user();
        $userId = $currentUser ? $currentUser->id() : 'system';

        try {
            $cmd = new LendBooksCommand($memberId, $bookIds, $userId);
            return response()->json($service->lend($cmd), 201);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function returnItemCanonical(string $id, string $itemId, ReturnItemService $service): JsonResponse
    {
        try {
            $res = $service->returnItem($id, $itemId);
            return response()->json($res);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }

    public function returnItem(Request $request, string $id, ReturnItemService $service): JsonResponse
    {
        $itemId = $request->input('loan_item_id');
        if (!$itemId) {
            return StandardApiResponse::error('VALIDATION_ERROR', 'loan_item_id es obligatorio', [], 422);
        }

        try {
            $res = $service->returnItem($id, $itemId);
            return response()->json($res);
        } catch (BusinessRuleViolation $e) {
            return StandardApiResponse::error($e->getErrorCode(), $e->getMessage(), [], $e->getHttpStatus());
        }
    }
}

class ReportController
{
    public function mostBorrowed(Request $request, MostBorrowedReportService $service): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $limit = (int)$request->query('limit', 10);

        if (!$from || !$to) {
            return StandardApiResponse::error(
                'INVALID_DATE_RANGE',
                'Los parámetros "from" y "to" son obligatorios en formato YYYY-MM-DD.',
                [],
                422
            );
        }

        if ($from > $to) {
            return StandardApiResponse::error(
                'INVALID_DATE_RANGE',
                'El rango de fechas es inválido: la fecha final es anterior a la inicial.',
                [],
                422
            );
        }

        $rows = $service->getMostBorrowed($limit, $from, $to);
        return response()->json([
            'from' => $from,
            'to' => $to,
            'rows' => $rows
        ]);
    }
}
