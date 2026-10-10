<?php

namespace App\Adapters\Outbound\Persistence\Mappers;

use App\Domain\Entities\Book;
use App\Domain\Entities\Category;
use App\Domain\Entities\Loan;
use App\Domain\Entities\Member;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Isbn;
use App\Adapters\Outbound\Persistence\Models\BookModel;
use App\Adapters\Outbound\Persistence\Models\CategoryModel;
use App\Adapters\Outbound\Persistence\Models\LoanModel;
use App\Adapters\Outbound\Persistence\Models\MemberModel;
use App\Adapters\Outbound\Persistence\Models\UserModel;

class CategoryMapper
{
    public static function toDomain(CategoryModel $model): Category
    {
        return new Category($model->id, $model->name);
    }
}

class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        return new User(
            $model->id,
            $model->username, // name defaults to username
            $model->username,
            $model->password_hash,
            $model->role
        );
    }
}

class MemberMapper
{
    public static function toDomain(MemberModel $model): Member
    {
        return new Member(
            $model->id,
            $model->full_name,
            'CC',
            $model->document_number,
            '',
            ''
        );
    }
}

class BookMapper
{
    public static function toDomain(BookModel $model): Book
    {
        return new Book(
            $model->id,
            $model->title,
            $model->author,
            new Isbn($model->isbn),
            $model->category_id,
            (int)$model->total_copies,
            (int)$model->available_copies,
            $model->cover_key,
            $model->category?->name,
            null,
            null,
            $model->deleted_at ? new \DateTimeImmutable($model->deleted_at) : null
        );
    }
}

class LoanMapper
{
    public static function toDomain(LoanModel $model): Loan
    {
        $loan = new Loan(
            $model->id,
            $model->member_id,
            $model->loaned_by_user_id,
            new \DateTimeImmutable($model->loaned_at)
        );

        if ($model->relationLoaded('items')) {
            foreach ($model->items as $it) {
                $loan->addItem(
                    $it->id,
                    $it->book_id,
                    $it->book_title,
                    $it->isbn,
                    $it->category_name,
                    $it->returned_at ? new \DateTimeImmutable($it->returned_at) : null
                );
            }
        }

        return $loan;
    }
}
