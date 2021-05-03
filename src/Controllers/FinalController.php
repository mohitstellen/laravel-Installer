<?php

namespace SonaliBhika\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use SonaliBhika\LaravelInstaller\Helpers\EnvironmentManager;
use SonaliBhika\LaravelInstaller\Helpers\FinalInstallManager;
use SonaliBhika\LaravelInstaller\Helpers\InstalledFileManager;
use SonaliBhika\LaravelInstaller\Events\LaravelInstallerFinished;
use SonaliBhika\LaravelInstaller\Helpers\DatabaseManager;
use DB;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param InstalledFileManager $fileManager
     * @return \Illuminate\View\View
     */
    public function finish(DatabaseManager $databaseManager,InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {

        $response = $databaseManager->migrateAndSeed();
        $finalMessages = $finalInstall->runFinal();
        if($response['status'] != 'error' && $finalMessages == ''){

          if(config('app.url') == 'production'){
            $environments = 'Live';
          }else{
            $environments = 'Maintenance';
          }

          DB::table('settings')->where('name', 'external_website_link')->update([	 'value' => config('app.url')]);
          DB::table('settings')->where('name', 'app_name')->update(['value' => config('app.name')]);
          DB::table('settings')->where('name', 'environment')->update(['value' => $environments]);

          $finalStatusMessage = $fileManager->update();
        }else{
          $finalStatusMessage = 'Error Check Your Database Credentials. You Might Be something Missing!';
        }
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);
        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
