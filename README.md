# Laravel-LmFile
(Under Development)

1. Generate Path Folder Storage otomatis saat upload file sesuai format tahun/bulan/ , tujuannya biar gampang aja nanti backup dan arsip sesuai custom per tahun/bulan
2. Bisa Compress Gambar
3. Generate Thumbnail
4. Multiple Upload/Update File
5. Akses File Gampang, Tidak perlu buat Relasi antar Model
6. Lebih Mudah Jika ada perubahan relasi file untuk one to one / one to Many  
7. Bisa Langsung Chaining Method dari Model (Pake Trait)
8. Semua data file akan disimpan dalam satu tabel "file"
9. ....? Wait

# Requirement
Install package Laravel "intervention/image" , untuk generate thumbnail dan compress gambar
https://image.intervention.io/v2/introduction/installation

## Use Trait in Model

```php
<?php
namespace App\Models;
use App\Utils\LmFileTrait;

class User extends Authenticatable
{
   use LmFileTrait;
...
}

```

## Using for Upload file

```php
<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

 public function store(UserRequest $request)
   {

  $user = User::find(1);
  $user->addFile($request->file_profile) // File Object from Form Request
         ->field("file_profile") // name field/coloumn in table Database 
         ->path("profile") // path store file in storage 
         ->compress(60) // compress Quality Image
         ->withThumb(100) //store file thumbnail with size ratio
         ->storeFile(); //store file and save to Database
...
}
```

## Multiple Upload
```php
 $user->addFile($request->file_profile)
         ->field("file_profile") 
         ->path("profile") 
         ->compress(60) 
         ->withThumb(100) 
         ->multiple() //only add this method, dont forget form input with array file value 
         ->storeFile(); 

```

## Access File
```php


//Access File Original
User::find(auth()->user()->id)->field('file_profile')->getFile()
// http://laravel-starter.test/storage/2023/08/profile/fotoku.png

// Access Thumbnail
User::find(auth()->user()->id)->field('file_profile')->getThumb()
// http://laravel-starter.test/storage/2023/08/profile/fotoku-thumb.png

// Access Multiple
User::find(auth()->user()->id)->field('file_profile')->getThumbs()
User::find(auth()->user()->id)->field('file_profile')->getFiles()

/* #items: array:3 [▼
    0 => "http://laravel-starter.test/storage/2023/08/cover/foto-red.png"
    1 => "http://laravel-starter.test/storage/2023/08/cover/foto-blue.jpg"
 ]
*/

//Access File With Attribute Model File, only add 'Attribute' name method 

//Access File Original
User::find(auth()->user()->id)->field('file_profile')->getFileAttribute() //single
User::find(auth()->user()->id)->field('file_profile')->getFilesAttribute() //multiple
User::find(auth()->user()->id)->field('file_profile')->getThumbAttribute() //single
User::find(auth()->user()->id)->field('file_profile')->getThumbsAttribute() //multiple

/*
#attributes: array:14 [▼
        "id" => 85
        "file_id" => "6af85a0f-ef25-475c-b6e9-7fc05768cdd6"
        "model_id" => 112277
        "name_origin" => "fotoku.png"
        "name_hash" => "logo-3-a717d3d0-1b1e-40e7-ba3c-d716bf7dc551-GeFsZqOfbod5dOGj7VaIr0zuCGxAjtrDWUZ51XOIkWZ1q5aXuI.png"
        "path" => "2023/08/profile/"
        "mime" => "image/png"
        "size" => "8226"
        "desc" => null
        "order" => 1
        "created_by" => 112277
        "created_at" => "2023-08-01 00:14:08"
        "updated_at" => "2023-08-01 00:14:08"
        "full_path" => "http://laravel-starter.test/storage/2023/08/cover/fotoku.jpg"
      ]
*/
```



