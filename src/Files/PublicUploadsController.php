<?php namespace Vebto\Files;

use Illuminate\Http\Request;
use Vebto\Bootstrap\Controller;

//use Session;
//use Illuminate\Support\Facades\Auth;
//use App\User;

class PublicUploadsController extends Controller {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var FileStorage
     */
    private $fileStorage;

    /**
     * @var Uploads
     */
    private $uploads;

    /**
     * UploadsController constructor.
     *
     * @param Request $request
     * @param FileStorage $fileStorage
     * @param Uploads $uploads
     */
    public function __construct(Request $request, FileStorage $fileStorage, Uploads $uploads)
    {
        $this->request = $request;
        $this->uploads = $uploads;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Store video or music files without attaching them to any database records.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function videos()
    {        
        $this->authorize('store', Upload::class);

        $this->validate($this->request, [
            'type'    => 'required|string|in:track,videoad',
            'files'   => 'required|array|min:1|max:5',
            'files.*' => 'required|file|mimeTypes:audio/mpeg,video/x-msvideo,video/mp4,application/mp4'
        ]);

        $type = $this->request->get('type');
        
//        $oldSession = Session::getId();

        $urls = array_map(function($file) use($type) {
            $config = ['disk' => 'public', 'path' => "{$type}_files"];
            return ['url' => $this->uploads->storePublic($file, $config)->first()->url];
        }, $this->request->all()['files']);        
                
//        if(Auth::check()){
//            $user = User::find(Auth::user()->id);
//            if($user && Auth::user()->session_id == $oldSession && $user->session_id == $oldSession ){
//                Auth::user()->session_id = Session::getId();
//                Auth::user()->save();
//            }            
//        }
        
        return response(['data' => $urls], 201);
    }

    /**
     * Store images on public disk.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function images() {

        $this->authorize('store', Upload::class);

        $this->validate($this->request, [
            'type'    => 'required_without:path|string|min:1',
            'path'    => 'required_without:type|string|min:1',
            'files'   => 'required|array|min:1|max:5',
            'files.*' => 'required|file|image'
        ]);

        $type = $this->request->get('type');
        $path = $this->request->has('path') ? $this->request->get('path') : "{$type}_images";
        
//        $oldSession = Session::getId();
        
        $config = ['disk' => 'public', 'path' => $path];
                
        if($type == 'genre_custom'){            
            $genreName = $this->request->get('genre');
            $genre = str_replace(' ', '-', strtolower(trim($genreName)));
            $config = ['disk' => 'client', 'path' => 'assets/images/genres/', 'genre' => $genre];   
            
            $uploads = $this->uploads->storePublicClient($this->request->all()['files'], $config);
        }else{
            $uploads = $this->uploads->storePublic($this->request->all()['files'], $config);
        }
        
//        if(Auth::check()){
//            $user = User::find(Auth::user()->id);
//            if($user && Auth::user()->session_id == $oldSession && $user->session_id == $oldSession ){
//                Auth::user()->session_id = Session::getId();
//                Auth::user()->save();
//            }            
//        }
        
        return response(['data' => $uploads], 201);
    }
        
}
