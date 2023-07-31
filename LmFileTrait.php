<?php

namespace App\Utils;

use App\Models\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;


trait LmFileTrait
{

   protected $path;
   protected $file;
   protected $field;
   protected $multiple = false;
   protected $getFile;
   protected $getFiles;
   protected $getThumb;
   protected $withThumb = false;
   protected $compress = false;
   protected $compressValue = 0;

   protected $withThumb_size = null;


   public function addFile($file)
   {
      $this->file =  $file;
      return $this;
   }


   public function path(string $path)
   {
      $this->path =  $path;
      return $this;
   }

   public function field(string $field)
   {
      $this->field =  $field;
      return $this;
   }


   public function multiple()
   {
      $this->multiple = true;
      return  $this;
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

   public function getPath($folder)
   {
      $tahun       = Carbon::now()->format('Y');
      $bulan       = Carbon::now()->format('m');
      $custom_path = $tahun . '/' . $bulan . '/' . $folder . '/';
      $path        = storage_path('app/public/' . $custom_path);

      if (!FacadesFile::isDirectory($path)) {
         FacadesFile::makeDirectory($path, 0777, true, true);
      }
      return $custom_path;
   }



   public function upload()
   {
      $file_uuid = Str::uuid();
      if ($this->multiple) {
         foreach ($this->file as $key => $value) {
            $this->uploadFileProcess($value, $key + 1,  $file_uuid);
         }
      } else {
         $this->uploadFileProcess($this->file, 1, $file_uuid);
      }
   }

   public function uploadFileProcess($file, $order, $file_uuid)
   {
    

      $name_origin = $file->getClientOriginalName();
      $name_uniqe  = RemoveSpace::removeDoubleSpace(pathinfo($name_origin, PATHINFO_FILENAME) . '-' . Str::uuid()->toString() . '-' . Str::random(50));
      $custom_path = $this->getPath($this->path);
      $name_file_with_extension  = $name_uniqe . '.' . strtolower($file->getClientOriginalExtension());
      $thumb_file_with_extension = $name_uniqe . '-thumb.' . $file->getClientOriginalExtension();

      if ($this->compress) {
         $imgWidth = Image::make($file->getRealPath())->width();
         $imgWidth -= $imgWidth * $this->compressValue / 100;
         $compressImage = Image::make($file->getRealPath())->resize($imgWidth, null, function ($constraint) {
            $constraint->aspectRatio();
         });
         $compressImage->stream();
         Storage::put('public/' . $custom_path . '/' . $name_file_with_extension,  $compressImage);
      } else {
         $file->storeAs('public/' . $custom_path, $name_file_with_extension);
      }


      if ($this->withThumb) {
         $this->generateThumbnail($file, 'public/' . $custom_path . '/' . $thumb_file_with_extension);
      }


      $check_file = Storage::exists('public/' . $custom_path . '/' . $name_file_with_extension);
      $check_file_thumb = Storage::exists('public/' . $custom_path . '/' . $thumb_file_with_extension);

      if ($check_file == false || $check_file_thumb == false) {
         throw new Exception("Error Processing Request", 1);
      }

      // if check file success upload store to DB

      $model = $this->getModel();
      $model->update([
         $this->field => $file_uuid
      ]);
      File::create([
         'file_id'     => $file_uuid,
         'model_id'    => $model->id,
         'name_origin' => $name_origin,
         'name_hash'   => $name_file_with_extension,
         'path'        => $custom_path,
         'created_by'  => auth()->user()->id,
         'mime'        => $file->getMimeType(),
         'order'       => $order,
         'size'        => $file->getSize(),
      ]);


      return $this;
   }

   public function generateThumbnail($file, $path)
   {
      $thumbImage = Image::make($file->getRealPath());
      $thumbImage->resize(null, $this->withThumb_size, function ($constraint) {
         $constraint->aspectRatio();
      });
      $thumbImage->stream();
      Storage::put($path, $thumbImage);
   }

   public function getFile()
   {

      $data = $this->field;
      $file_id = $this->getModel()->$data;

      $file = File::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
         return $item;
      });
      return $file->toArray()[0]['full_path'];
   }

   public function getFiles()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;

      $file = File::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
         return $item;
      });
      return $file->pluck('full_path');
   }

   public function getFilesAttribute()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;

      $file = File::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
         return $item;
      });
      return $file;
   }

   public function getFileAttribute()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;

      $file = File::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $item['full_path'] = url('storage/' . $item->path . $item->name_hash);
         return $item;
      });
      return $file->first();
   }



   public function getThumb()
   {
      return $this->makeThumbsAttribute()->toArray()[0]['full_path'];
   }

   public function getThumbs()
   {

      return $this->makeThumbsAttribute()->pluck('full_path');
   }

   public function makeThumbsAttribute()
   {
      $data = $this->field;
      $file_id = $this->getModel()->$data;
      $file = File::where('file_id',  $file_id)->where('model_id', $this->getModel()->id)->orderBy('order', 'ASC')->get();
      $file->map(function ($item) {
         $addString = "-thumb";
         $fileInfo = pathinfo($item->name_hash);
         $newFileName = $fileInfo['filename'] . $addString . '.' . $fileInfo['extension'];
         $item['full_path'] = url('storage/' . $item->path . $newFileName);
         return $item;
      });

      return $file;
   }
}
