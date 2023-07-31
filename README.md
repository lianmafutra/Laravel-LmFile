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
