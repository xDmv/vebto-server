<?php namespace Vebto\Bootstrap;

class BootstrapController extends Controller
{
    /**
     * Get data needed to bootstrap the application.
     *
     * @param BootstrapData $bootstrapData
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBootstrapData(BootstrapData $bootstrapData)
    {
        return response(['data' => $bootstrapData->get()]);
    }
}
