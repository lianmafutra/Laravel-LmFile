# Laravel-LmFile
Laravel Easy Upload Using Trait on Model

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
