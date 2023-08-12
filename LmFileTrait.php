<?php

namespace App\Utils;

use App\Models\File as ModelsFile;
use App\Utils\LmFile\CompressImage;
use App\Utils\LmFile\FilterExtension;
use App\Utils\LmFile\GeneratePath;
use App\Utils\LmFile\GenerateThumbnail;
use Exception;
use RahulHaque\Filepond\Facades\Filepond;
use Storage;
use Str;

trait LmFileTrait
{

   protected $file = null;
   protected $path = "";
   protected $field = "";
   protected $custom_path = "";

   protected $getThumb;
   protected $withThumb = false;
   protected $withThumb_size = null;

   protected $extension = "";
   protected $filterExtension;


   protected $compress = false;
   protected $compressValue = 0;
   protected $sizeToCompress = 40000;

   public function __construct()
   {
      parent::__construct();
   }

   public function addFile($file)
   {
      $this->file =  $file;
      return $this;
   }

   public function path(string $path)
   {
      $this->path =  $path;
      $this->custom_path = (new GeneratePath())->get($path);
      return $this;
   }

   public function field(string $field)
   {
      $this->field =  $field;
      return $this;
   }


   // allowed extension
   public function extension(array $extension)
   {
      $this->extension = $extension;
      $this->filterExtension = new FilterExtension();
      return $this;
   }

   public function withThumb($size)
   {

      $this->withThumb_size = $size;
      $this->withThumb      = true;

      return  $this;
   }


   public function compress($value)
   {
      $this->compressValue = $value;
      $this->compress      = true;
      return  $this;
   }


   public function storeFileSingle()
   {
      try {

         $file_uuid = Str::uuid(); // UUID always generate new upload image
         $field = $this->field;

         $fileRequest = Filepond::field($this->file);

         // filter extension
         if ($this->extension) {
            $this->filterExtension->run(
               $fileRequest->getFile(),
               $this->extension
            );
         }

         // check file empty to delete
         if ($this->file == null) {
            $deleteOldFile = ModelsFile::where('file_id', $this->getModel()->$field)->first();
            if ($deleteOldFile) {
               Storage::disk('public')->delete($deleteOldFile->path . $deleteOldFile->name_hash);
               Storage::disk('public')->delete($deleteOldFile->path . $this->searchThumb($deleteOldFile->name_hash));
               $deleteOldFile->delete();
               return true;
            }
            return true;
         }

         $filepond = $fileRequest->moveTo(
            $this->custom_path . Str::random(15)
         );



         // generate Thumbnail
         if ($this->withThumb) {

            $thumb = new GenerateThumbnail();
            $thumb->run(
               $fileRequest->getFile(),
               $this->withThumb_size,
               $this->custom_path . $filepond['filename'] . '-thumb.' . $filepond['extension']
            );
         }

         // check and store compress file
         if ($this->compress) {
            if ($fileRequest->getFile()->getSize() >= $this->sizeToCompress) {
               $fileCompress = new CompressImage();
               $fileCompress = $fileCompress->run($fileRequest->getFile(), $this->compressValue);
               Storage::disk('public')->put(
                  $this->custom_path .
                     $filepond['filename'] . '.' . $filepond['extension'],
                  $fileCompress
               );
            }
         }

         $this->getModel()->update([
            $this->field => $file_uuid
         ]);

         ModelsFile::create([
            'file_id'     => $file_uuid,
            'model_id'    => $this->getModel()->id,
            'name_origin' => $filepond['originname'],
            'name_hash'   => $filepond['filename'] . '.' . $filepond['extension'],
            'path'        => $this->custom_path,
            'created_by'  => auth()->user()->id,
            'mime'        => $filepond['mime'],
            'extension'   => $filepond['extension'],
            'order'       => 1,
            'size'        => $filepond['size'],
         ]);
      } catch (\Throwable $th) {
         // check and store compress file
         if ($this->compress) {
            if ($fileRequest->getFile()->getSize() >= $this->sizeToCompress) {
               $fileCompress = new CompressImage();
               $fileCompress = $fileCompress->run($fileRequest->getFile(), $this->compressValue);
               Storage::disk('public')->put(
                  $this->custom_path .
                     $filepond['filename'] . '.' . $filepond['extension'],
                  $fileCompress
               );
            }
         }
         throw new Exception("Error Payload Filepond", 1);
      }
   }


   public function updateFileMultiple()
   {
      try {
         $field = $this->field;

         $this->deleteDataOnUpdate();
         $file_uuid = $this->getModel()->$field ? $this->getModel()->$field : Str::uuid();

         $newFileServerId = array_filter($this->file, function ($element) {
            return preg_match('/^[a-zA-Z0-9\/+=]+$/', $element);
         });

         if ($newFileServerId != []) {

            $fileRequest = Filepond::field($newFileServerId);

            $filepond = $fileRequest->moveTo(
               $this->custom_path . Str::random(15)
            );


            foreach ($filepond as $index => $file) {
               if ($this->extension) {
                  $this->filterExtension->run(
                     $fileRequest->getFile()[$index],
                     $this->extension
                  );
               }

               if ($this->withThumb) {
                  $thumb = new GenerateThumbnail();
                  $thumb->run(
                     $fileRequest->getFile()[$index],
                     $this->withThumb_size,
                     $this->custom_path .
                        $this->custom_path . $file['filename'] . '-thumb.' . $file['extension']
                  );
               }

               if ($this->compress) {
                  $fileCompress = new CompressImage();
                  $fileCompress = $fileCompress->run($fileRequest->getFile()[$index], $this->compressValue);
                  Storage::disk('public')->put(
                     $this->custom_path .
                        $file['filename'] . '.' . $file['extension'],
                     $fileCompress
                  );
                  if ($fileRequest->getFile()[$index]->getSize() >= $this->sizeToCompress) {
                  }
               }

               $this->getModel()->update([
                  $this->field => $file_uuid
               ]);

               ModelsFile::create([
                  'file_id'     => $file_uuid,
                  'model_id'    => $this->getModel()->id,
                  'name_origin' => $file['originname'],
                  'name_hash'   => $file['filename'] . '.' . $file['extension'],
                  'path'        => $this->custom_path,
                  'created_by'  => auth()->user()->id,
                  'mime'        => $file['mime'],
                  'extension'   => $file['extension'],
                  'order'       => $index + 1,
                  'size'        => $file['size'],
               ]);
            }
         }
      } catch (\Throwable $th) {
         throw new Exception($th, 1);
      }
   }

   public function updateFileSingle()
   {

      try {
         $field = $this->field;
         $file_uuid = $this->getModel()->$field ? $this->getModel()->$field : Str::uuid();
         $fileRequest = Filepond::field($this->file);
         $this->deleteDataOnUpdate();

         if ($this->extension) {
            // filter extension
            $this->filterExtension->run(
               $fileRequest->getFile(),
               $this->extension
            );
         }

         $filepond = $fileRequest->moveTo(
            $this->custom_path . Str::random(15)
         );

         // generate Thumbnail
         if ($this->withThumb) {

            $thumb = new GenerateThumbnail();
            $thumb->run(
               $fileRequest->getFile(),
               $this->withThumb_size,
               $this->custom_path . $filepond['filename'] . '-thumb.' . $filepond['extension']
            );
         }


         // check and store compress file
         if ($this->compress) {
            if ($fileRequest->getFile()->getSize() >= $this->sizeToCompress) {
               $fileCompress = new CompressImage();
               $fileCompress = $fileCompress->run($fileRequest->getFile(), $this->compressValue);
               Storage::disk('public')->put(
                  $this->custom_path .
                     $filepond['filename'] . '.' . $filepond['extension'],
                  $fileCompress
               );
            }
         }


         $this->getModel()->update([
            $this->field => $file_uuid
         ]);

         ModelsFile::create([
            'file_id'     => $file_uuid,
            'model_id'    => $this->getModel()->id,
            'name_origin' => $filepond['originname'],
            'name_hash'   => $filepond['filename'] . '.' . $filepond['extension'],
            'path'        => $this->custom_path,
            'created_by'  => auth()->user()->id,
            'mime'        => $filepond['mime'],
            'extension'   => $filepond['extension'],
            'order'       => 1,
            'size'        => $filepond['size'],
         ]);
         return true;
      } catch (\Throwable $th) {
      }
   }

   public function storeFileMultiple()
   {
      $file_uuid = Str::uuid(); // UUID always generate new upload image

      $this->getModel()->update([
         $this->field => $file_uuid
      ]);

      if ($this->extension) {
         foreach ($this->file as $key => $value) {
            $this->filterExtension->run(
               Filepond::field($this->file)->getFile(),
               $this->extension
            );
         }
      }

      $fileRequest = Filepond::field($this->file);

      $filepond = $fileRequest->moveTo(
         $this->custom_path . Str::random(15)
      );

      foreach ($filepond as $index => $file) {
         if ($this->withThumb) {
            $thumb = new GenerateThumbnail();
            $thumb->run(
               $fileRequest->getFile()[$index],
               $this->withThumb_size,
               $this->custom_path .
                  $file['filename'] . '-thumb.' . $file['extension']
            );
         }

         if ($this->compress) {
            if ($fileRequest->getFile()[$index]->getSize() >= $this->sizeToCompress) {
               $fileCompress = new CompressImage();
               $fileCompress = $fileCompress->run($fileRequest->getFile()[$index], $this->compressValue);
               Storage::disk('public')->put(
                  $this->custom_path .
                     $file['filename'] . '.' . $file['extension'],
                  $fileCompress
               );
            }
         }

         ModelsFile::create([
            'file_id'     => $file_uuid,
            'model_id'    => $this->getModel()->id,
            'name_origin' => $file['originname'],
            'name_hash'   => $file['filename'] . '.' . $file['extension'],
            'path'        => $this->custom_path,
            'created_by'  => auth()->user()->id,
            'mime'        => $file['mime'],
            'extension'   => $file['extension'],
            'order'       => $index + 1,
            'size'        => $file['size'],
         ]);
      }
   }

   public function storeFile()
   {
      if (is_array($this->file)) {
         $this->storeFileMultiple();
      } else {
         $this->storeFileSingle();
      }
   }

   public function updateFile()
   {
      if (is_array($this->file)) {

         $this->updateFileMultiple();
      } else {

         $this->updateFileSingle();
      }
   }

   private function deleteDataOnUpdate()
   {
      $field = $this->field;

      if (is_array($this->file)) {
         $filenames = [];

         foreach ($this->file as $url) {
            $filenames[] = basename($url);
         }

         $oldFiles = ModelsFile::where('file_id', $this->getModel()->$field)->pluck('name_hash');

         $filesToDelete = $oldFiles->diff($filenames);
         foreach (ModelsFile::whereIn('name_hash', $filesToDelete->toArray())->get() as $key => $value) {
            Storage::disk('public')->delete($value->path . $value->name_hash);
            Storage::disk('public')->delete($value->path . $this->searchThumb($value->name_hash));
            ModelsFile::where('name_hash', $value->name_hash)->delete();
         }
      } else {

         $oldFiles = ModelsFile::where('file_id', $this->getModel()->$field)->first();
         if ($oldFiles) {
            if ($oldFiles->name_hash != basename($this->file)) {
               Storage::disk('public')->delete($oldFiles->path . $oldFiles->name_hash);
               Storage::disk('public')->delete($oldFiles->path . $this->searchThumb($oldFiles->name_hash));
               $oldFiles->delete();
            }
         }
      }
   }

   public function getFilepond()
   {
      $dataCollection = [];

      if ($this->makeFileAttribute()->count() > 0) {

         if ($this->makeFileAttribute()->toArray()[0]['mime'] == "pdf") {
            $dataObject = [
               "source" => $this->makeFileAttribute()->toArray()[0]['full_path'],
               "options" => [
                  "type" => "local",
                  "file" => [
                     "name" => $this->makeFileAttribute()->toArray()[0]['name_origin'],
                     "size" => $this->makeFileAttribute()->toArray()[0]['size'],
                  ],
                  "metadata" => [
                     "poster" => asset('img/pdf-thumb.png'),
                  ]
               ]
            ];
         } else {
            $dataObject = [
               "source" => $this->makeFileAttribute()->toArray()[0]['full_path'],
               "options" => [
                  "type" => "local",
               ],
               "metadata" => [
                  "poster" => asset('img/pdf-thumb.png'),
               ]
            ];
         }
         array_push($dataCollection, $dataObject);
         return $dataCollection;
      } else {
      }
   }

   public function getFileponds()
   {
      $dataCollection = [];
      $dataObject = [];

      if ($this->makeFileAttribute()->count() > 0 && $this->makeFileAttribute()->toArray()[0]['mime'] == "pdf") {
         foreach ($this->makeFileAttribute() as $key => $value) {
            $dataObject = [
               "source" => $value->full_path,
               "options" => [
                  "type" => "local",
                  "file" => [
                     "name" =>  $value->name_origin,
                     "size" =>  $value->size,
                  ],
                  "metadata" => [
                     "poster" => asset('img/pdf-thumb.png'),
                  ]
               ]
            ];
            array_push($dataCollection, $dataObject);
         }
      } else {


         foreach ($this->makeFileAttribute() as $key => $value) {
            $dataObject = [
               "source" => $value->full_path,
               "options" => [
                  "type" => "local",
               ]
            ];
            array_push($dataCollection, $dataObject);
         }
      }
      return $dataCollection;
   }




   public function getFile()
   {
      if ($this->makeFileAttribute()->toArray()) {
         return $this->makeFileAttribute()->toArray()[0]['full_path'];
      }
   }

   public function getThumb()
   {
      if ($this->makeThumbsAttribute()->toArray()) {
         return $this->makeThumbsAttribute()->toArray()[0]['full_path'];
      }
      return "";
   }

   public function getThumbs()
   {
      return $this->makeThumbsAttribute()->pluck('full_path');
   }

   public function getFiles()
   {
      return $this->makeFileAttribute()->pluck('full_path');
   }

   public function makeFileAttribute()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;
      $file = ModelsFile::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
         return $item;
      });

      return $file;
   }

   public function makeThumbsAttribute()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;
      $file = ModelsFile::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         // check thumbnail availbale or not , 
         $exists = Storage::disk('public')->exists($item->path . $this->searchThumb($item->name_hash));

         if (!$exists) {
            // return default original 
            $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
            return $item;
         }

         // return default thumbnail 
         $addString = "-thumb";
         $fileInfo = pathinfo($item->name_hash);
         $newFileName = $fileInfo['filename'] . $addString . '.' . $fileInfo['extension'];
         $item['full_path'] = url('storage/' . $item->path . $newFileName);
         return $item;
      });

      return $file;
   }


   // add name file last char with -thumb to searc delete and others
   private function searchThumb($name_hash)
   {
      $addString = "-thumb";
      $fileInfo = pathinfo($name_hash);
      $thumbName = $fileInfo['filename'] . $addString . '.' . $fileInfo['extension'];
      return $thumbName;
   }
}
