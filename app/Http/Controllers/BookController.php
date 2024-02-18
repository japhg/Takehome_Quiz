<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Inertia\Inertia;
use App\Models\Image;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function showBook()
    {

        $authors = DB::table('books')
            ->join('authors', 'authors.id', '=', 'books.author_id')
            ->join('images', 'books.id', '=', 'images.transactionable_id')
            ->where('images.transactionable_type', 'App\Models\Book')
            ->select('books.*', 'authors.author_name', 'images.filename')
            ->get();

        return Inertia::render('Book/index', [
            'books' => $authors,
            'links' => [
                public_path('storage') => storage_path('app/public/images')
            ]
        ]);
    }

    public function addBook()
    {
        $authors = Author::all();
        return Inertia::render('Book/add', [
            'authors' => $authors,
        ]);
    }

    public function storeBook(Request $request)
    {
        $data = $request->validate([
            'author' => 'required',
            'book_title' => 'required',
            'date_published' => 'required|date',
            'image' => 'required|image|mimes:jpg,png,gif,bmp',
        ]);

        $book = new Book();
        $book->book_title = $data['book_title'];
        $book->date_published = $data['date_published'];
        $book->author_id = $data['author'];
        $book->save();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->getClientOriginalName();
            $image->storeAs('public/images', $filename);

            $imageModel = new Image();
            $imageModel->filename = $filename;
            $book->images()->save($imageModel);

            return redirect(route('book.showBook'))->with('success', 'The book has been added!');
        }

        return redirect(route('book.showBook'))->with('error', 'The book is not added!');

    }

    // public function editBook($id)
    public function editBook($id)
    {
        $books = DB::table('books')
            ->join('authors', 'authors.id', '=', 'books.author_id')
            ->join('images', 'books.id', '=', 'images.transactionable_id')
            ->where('books.id', $id)
            ->where('images.transactionable_type', 'App\Models\Book')
            ->select('books.*', 'authors.author_name', 'images.filename')
            ->first();

        $authors = Author::all();
        return Inertia::render('Book/edit', [
            'books' => $books,
            'authors' => $authors,
            'links' => [
                public_path('storage') => storage_path('app/public/images')
            ],
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }


    public function updateBook(Request $request, Book $books)
    {
        $data = $request->validate([
            'author_id' => 'required',
            'book_title' => 'required',
            'date_published' => 'required|date',
            'image' => $request->hasFile('image') ? 'required|image|mimes:jpg,png,gif,bmp' : 'nullable',
        ]);

        $books->update([
            'author_id' => $data['author_id'],
            'book_title' => $data['book_title'],
            'date_published' => $data['date_published'],
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $image->getClientOriginalName();
            $image->storeAs('public/images', $filename);

            if ($books->images()) {
                // Update the filename of the existing image
                $books->images()->updateOrCreate(
                    [],
                    ['filename' => $filename]
                );
            }
        }

        return redirect(route('book.showBook'))->with('success', 'The book has been updated!');
    }

    public function deleteBook($id){
        $book = Book::findOrFail($id);

        $book->images()->delete();
        $book->delete();

        return redirect(route('book.showBook'))->with('success', 'The book has been deleted!');
    }

}
