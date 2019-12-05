<?php namespace Vebto\Files;

use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;

class Uploads {

    /**
     * Upload model.
     *
     * @var Upload
     */
    private $upload;

    /**
     * Storage service instance.
     *
     * @var FileStorage
     */
    private $storage;

    /**
     * UploadsCreator service instance.
     *
     * @var UploadsCreator
     */
    private $uploadsCreator;

    /**
     * Uploads constructor.
     *
     * @param Upload $upload
     * @param FileStorage $storage
     * @param UploadsCreator $uploadsCreator
     */
    public function __construct(Upload $upload, FileStorage $storage, UploadsCreator $uploadsCreator)
    {
        $this->upload = $upload;
        $this->storage = $storage;
        $this->uploadsCreator = $uploadsCreator;
    }

    /**
     * Create public uploads from specified file data.
     *
     * @param array $files
     * @param array $config
     * @return Collection
     */
    public function storePublic($files, $config)
    {
        if ( ! is_array($files)) $files = [$files];

        $files = $this->saveFilesToDisk($files, $config);

        return $this->uploadsCreator->create($files, $config);
    }
    
    /**
     * Create public/client uploads from specified file data.
     *
     * @param array $files
     * @param array $config
     * @return Collection
     */
    public function storePublicClient($files, $config)
    {
        if ( ! is_array($files)) $files = [$files];

        $files = $this->saveFilesToDisk($files, $config);

//        $this->uploadsCreator->create($files, $config);
        
        return $files;
    }

    /**
     * Create uploads from specified file data.
     *
     * This will upload files to filesystem if needed
     * and create Upload models for those files.
     *
     * @param array $files
     * @return Collection
     */
    public function store($files)
    {
        if ( ! is_array($files)) $files = [$files];

        $files = $this->saveFilesToDisk($files);

        return $this->uploadsCreator->create($files);
    }

    /**
     * Save specified files to disk.
     *
     * @param array $files
     *
     * @param array $config
     * @return array
     */
    private function saveFilesToDisk($files, $config = [])
    {
        foreach ($files as $key => $file) {

            //convert UploadedFile instances to array
            if (is_a($file, UploadedFile::class)) {
                $files[$key] = $file = $this->uploadedFileToArray($file);
            }

            //store file to disk and set file name on file configuration
            if (Arr::get($config, 'disk') === 'public') {
                $files[$key]['file_name'] = $this->saveFileToPublicDisk($file, $config);
            }elseif(Arr::get($config, 'disk') === 'client'){                
                $files[$key]['file_name'] = $this->saveFileToClientDisk($file, $config);
            }else {
                $files[$key]['file_name'] = $this->storage->put($file['contents']);
            }

            //unset contents from file configuration so
            //temp file resource is released from memory
            unset($files['contents']);
        }

        return $files;
    }

    /**
     * Store specified file on public disk.
     *
     * @param array $file
     * @param array $config
     * @return string
     */
    private function saveFileToPublicDisk($file, $config)
    {
        return $this->storage->put(
            $file['contents'],
            $config['path'],
            str_random(40).'.'.$file['extension'],
            ['visibility' => 'public', 'disk' => 'public']
        );
    }
    
    private function saveFileToClientDisk($file, $config)
    {
        return $this->storage->putClient(
            $file['contents'],
            $config['path'],
            $config['genre'].'.'.$file['extension'],
            ['visibility' => 'public', 'disk' => 'client'],
            $config['genre']
        );    
    }

    /**
     * Convert laravel|symfony UploadedFile instance into array.
     *
     * @param UploadedFile $fileData
     * @return array
     */
    private function uploadedFileToArray(UploadedFile $fileData)
    {
        return [
            'original_name' => $fileData->getClientOriginalName(),
            'mime_type' => $fileData->getMimeType(),
            'size' => $fileData->getClientSize(),
            'extension' => $fileData->guessExtension(),
            'contents'  => $fileData
        ];
    }
}