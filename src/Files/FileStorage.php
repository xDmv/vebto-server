<?php namespace Vebto\Files;

use Vebto\Files\Upload;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;

use Intervention\Image\Facades\Image as Image;

class FileStorage {

    /**
     * Upload model.
     *
     * @var Upload
     */
    private $upload;

    /**
     * Laravel Storage service instance.
     *
     * @var FilesystemAdapter
     */
    private $laravelStorage;

    /**
     * Storage constructor.
     *
     * @param Upload $upload
     * @param FilesystemManager $laravelStorage
     */
    public function __construct(Upload $upload, FilesystemManager $laravelStorage)
    {
        $this->upload = $upload;
        $this->laravelStorage = $laravelStorage;
    }

    /**
     * Save specified files to currently active flysystem disk.
     *
     * @param mixed $contents
     * @param string $path
     * @param string $fileName
     * @param array $options
     *
     * @return string
     */
    public function put($contents, $path = 'uploads', $fileName = null, $options = [])
    {
        if ( ! $fileName) $fileName = str_random(40);

        $disk = Arr::pull($options, 'disk');
                
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            $this->laravelStorage->disk($disk)->putFileAs($path, $contents, $fileName, $options);
        } else {
            $this->laravelStorage->disk($disk)->put($path.'/'.$fileName, $contents, $options);
        }
        
        return $fileName;
    }
    
    public function putClient($contents, $path = 'uploads', $fileName = null, $options = [], $genre='')
    {
        if ( ! $fileName) $fileName = str_random(40);

        $disk = Arr::pull($options, 'disk');
        
        if($disk == 'client'){            
            $existsJPG = $this->laravelStorage->disk($disk)->exists($path.'/'.$genre.'.jpg');
            if($existsJPG){
                $this->laravelStorage->disk($disk)->delete($path.'/'.$genre.'.jpg');
            }   
        }
        
        if($fileName != $genre.'jpg'){
            $fileName = 'temp_'.$fileName;
        }
        
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            $this->laravelStorage->disk($disk)->putFileAs($path, $contents, $fileName, $options);
        } else {
            $this->laravelStorage->disk($disk)->put($path.'/'.$fileName, $contents, $options);
        }
        
        $jpg = (string) Image::make(public_path().'/client/assets/images/genres/'.$fileName)->encode('jpg', 75);
        
        $new_fileName = $genre.'.jpg';
                
        $this->laravelStorage->disk($disk)->put($path.'/'.$new_fileName, $jpg, $options);
        
        if($fileName != $new_fileName){
            $this->laravelStorage->disk($disk)->delete($path.'/'.$fileName);
        }
                
        return $new_fileName;
    }
    
    
}