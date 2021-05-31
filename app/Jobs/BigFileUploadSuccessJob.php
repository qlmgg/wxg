<?php

namespace App\Jobs;

use App\Exceptions\NoticeException;
use App\Models\AlibabaCloudService;
use App\Models\BigFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OSS\Core\OssException;

class BigFileUploadSuccessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AlibabaCloudService;

    protected $bigFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BigFile $bigFile)
    {
        $this->bigFile = $bigFile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bigFile = $this->bigFile;

        $client = self::getOssClient();
        $bucket = self::getOssBucket();
        try {
            $objectMeta = $client->getObjectMeta($bucket, $bigFile->path);

            $bigFile->size = data_get($objectMeta, 'content-length');
            $bigFile->content_type = data_get($objectMeta, 'info.content_type');
            $bigFile->is_exist = true;
            $bigFile->save();

        } catch (OssException $e) {
            throw new NoticeException($e->getMessage());
        }
    }
}
