# Laravel-LmFile
( Upload File Gak Pake Ribet, Males setting2 tiap new project ya udah buat kayak gini aja)

1. Generate Path Folder Storage otomatis sesuai format tahun/bulan/file
2. Bisa Compress Gambar
3. Generate Thumbnail
4. Multiple Upload
5. Bisa Langsung Chaining Method dari Model (Pake Trait)
6. 


## Using in Model with Trait

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
  $user->addFile($request->file_profile)
         ->field("file_profile") // name field/coloumn in table Database 
         ->path("profile") // path store file in storage 
         ->compress(60) // compress Quality Image
         ->withThumb(100) //store file thumbnail with size ratio
         ->upload(); //store file and save to Database
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
         ->upload(); 

```


