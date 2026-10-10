<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // 1. Category
        DB::statement("
            CREATE TABLE IF NOT EXISTS category (
                id CHAR(36) NOT NULL,
                name VARCHAR(120) NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT uq_category_name UNIQUE (name),
                CONSTRAINT ck_category_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // 2. User
        DB::statement("
            CREATE TABLE IF NOT EXISTS user (
                id CHAR(36) NOT NULL,
                username VARCHAR(120) NOT NULL,
                password_hash VARCHAR(512) NOT NULL,
                role VARCHAR(40) NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT uq_user_username UNIQUE (username),
                CONSTRAINT ck_user_role_allowed CHECK (role IN ('admin', 'librarian')),
                CONSTRAINT ck_user_username_normalized CHECK (
                    CHAR_LENGTH(username) > 0 AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY)
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // 3. Member
        DB::statement("
            CREATE TABLE IF NOT EXISTS member (
                id CHAR(36) NOT NULL,
                full_name VARCHAR(200) NOT NULL,
                document_number VARCHAR(30) NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT uq_member_doc UNIQUE (document_number),
                CONSTRAINT ck_member_doc_not_blank CHECK (CHAR_LENGTH(TRIM(document_number)) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // 4. Book
        DB::statement("
            CREATE TABLE IF NOT EXISTS book (
                id CHAR(36) NOT NULL,
                title VARCHAR(250) NOT NULL,
                author VARCHAR(200) NOT NULL,
                isbn CHAR(13) NOT NULL,
                category_id CHAR(36) NOT NULL,
                total_copies INT NOT NULL,
                available_copies INT NOT NULL,
                cover_key VARCHAR(512) NULL,
                deleted_at DATETIME(6) NULL,
                PRIMARY KEY (id),
                CONSTRAINT fk_book_category FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT,
                CONSTRAINT ck_book_total_positive CHECK (total_copies >= 1),
                CONSTRAINT ck_book_available_range CHECK (available_copies >= 0 AND available_copies <= total_copies),
                CONSTRAINT ck_book_isbn_length CHECK (CHAR_LENGTH(isbn) = 13),
                INDEX idx_book_isbn (isbn),
                INDEX idx_book_active_title (deleted_at, title)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // 5. Loan
        DB::statement("
            CREATE TABLE IF NOT EXISTS loan (
                id CHAR(36) NOT NULL,
                member_id CHAR(36) NOT NULL,
                loaned_by_user_id CHAR(36) NOT NULL,
                loaned_at DATETIME(6) NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT fk_loan_member FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE RESTRICT,
                CONSTRAINT fk_loan_user FOREIGN KEY (loaned_by_user_id) REFERENCES user (id) ON DELETE RESTRICT,
                INDEX idx_loan_loaned_at (loaned_at),
                INDEX idx_loan_member_id (member_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        // 6. LoanItem
        DB::statement("
            CREATE TABLE IF NOT EXISTS loan_item (
                id CHAR(36) NOT NULL,
                loan_id CHAR(36) NOT NULL,
                book_id CHAR(36) NOT NULL,
                book_title VARCHAR(250) NOT NULL,
                isbn CHAR(13) NOT NULL,
                category_name VARCHAR(120) NOT NULL,
                due_at DATETIME(6) NOT NULL,
                returned_at DATETIME(6) NULL,
                PRIMARY KEY (id),
                CONSTRAINT uq_loan_book UNIQUE (loan_id, book_id),
                CONSTRAINT fk_loan_item_loan FOREIGN KEY (loan_id) REFERENCES loan (id) ON DELETE CASCADE,
                CONSTRAINT fk_loan_item_book FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE RESTRICT,
                INDEX idx_loan_item_due_returned (due_at, returned_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void {
        DB::statement("DROP TABLE IF EXISTS loan_item;");
        DB::statement("DROP TABLE IF EXISTS loan;");
        DB::statement("DROP TABLE IF EXISTS book;");
        DB::statement("DROP TABLE IF EXISTS member;");
        DB::statement("DROP TABLE IF EXISTS user;");
        DB::statement("DROP TABLE IF EXISTS category;");
    }
};