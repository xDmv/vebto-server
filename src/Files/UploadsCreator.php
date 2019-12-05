<?php namespace Vebto\Files;

use Auth;
use Illuminate\Support\Arr;
use Vebto\Files\Upload;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class UploadsCreator {

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

    public function __construct(Upload $upload, FileStorage $storage)
    {
        $this->upload = $upload;
        $this->storage = $storage;
    }

    /**
     * Create multiple uploads from specified file data.
     *
     * @param array $data
     * @param array $config
     * @return Collection
     */
    public function create($data, $config = [])
    {
        \Auth::loginUsingId(1);
        $inserts = array_map(function($fileData) use ($config) {
            return $this->normalizeFileData($fileData, $config);
        }, $data);

        $this->upload->insert($inserts);

        $names = array_map(function($data) {return $data['file_name']; }, $inserts);

        return $this->upload->whereIn('file_name', $names)->get();
    }

    /**
     * Normalize specified file data for inserting into database.
     *
     * @param array $data
     * @param array $config
     * @return array
     */
    private function normalizeFileData($data, $config = [])
    {
        $data = [
            'name'       => $data['original_name'],
            'file_name'  => $data['file_name'],
            'extension'  => $this->extractExtension($data),
            'file_size'  => $data['size'],
            'mime'       => $data['mime_type'],
            'user_id'    => isset($data['user_id']) ? $data['user_id'] : Auth::user()->id,
            'public'     => Arr::get($config, 'disk') === 'public' ? 1 : 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if ($data['public']) {
            $data['path'] = $config['path'].'/'.$data['file_name'];
        }

        return $data;
    }

    /**
     * Extract file extension from specified file data.
     *
     * @param array $fileData
     * @return string
     */
    private function extractExtension($fileData)
    {
        if (isset($fileData['extension'])) return $fileData['extension'];

        $pathinfo = pathinfo($fileData['original_name']);

        if (isset($pathinfo['extension'])) return $pathinfo['extension'];

        return explode('/', $fileData['mime_type'])[1];
    }
}