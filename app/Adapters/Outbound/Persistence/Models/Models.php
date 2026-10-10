<?php

namespace App\Adapters\Outbound\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryModel extends Model
{
    protected $table = 'category';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'name'];
}

class UserModel extends Model
{
    protected $table = 'user';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'username', 'password_hash', 'role'];
}

class MemberModel extends Model
{
    protected $table = 'member';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'full_name', 'document_number'];

    public function loans()
    {
        return $this->hasMany(LoanModel::class, 'member_id');
    }
}

class BookModel extends Model
{
    use SoftDeletes;

    protected $table = 'book';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'id', 'title', 'author', 'isbn', 'category_id',
        'total_copies', 'available_copies', 'cover_key', 'deleted_at'
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}

class LoanModel extends Model
{
    protected $table = 'loan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'member_id', 'loaned_by_user_id', 'loaned_at'];

    public function member()
    {
        return $this->belongsTo(MemberModel::class, 'member_id');
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'loaned_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(LoanItemModel::class, 'loan_id');
    }
}

class LoanItemModel extends Model
{
    protected $table = 'loan_item';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'id', 'loan_id', 'book_id', 'book_title', 'isbn', 'category_name', 'due_at', 'returned_at'
    ];

    public function loan()
    {
        return $this->belongsTo(LoanModel::class, 'loan_id');
    }

    public function book()
    {
        return $this->belongsTo(BookModel::class, 'book_id');
    }
}
