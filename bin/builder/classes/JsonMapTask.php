<?php

use Task;

class JsonMapTask extends Task {

    /** @var string|null */
    private $folder;
    /** @var string|null */
    private $commit;

    /**
     * @param string $folder
     * @return void
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    /**
     * @param string $commit
     * @return void
     */
    public function setCommit($commit)
    {
        $this->commit = $commit;
    }

    /**
     * @return void
     */
    public function init()
    {
    }

    /**
     * @return void
     */
    public function main()
    {
        $folder = $this->folder;
        $commit = $this->commit;

        $list = [];
        $iterator = new DirectoryIterator($folder);
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $list[$fileInfo->getFilename()] = [
                'file' => $fileInfo->getFilename(),
                'size' => $fileInfo->getSize(),
                'date' =>  $fileInfo->getMTime()
            ];
        }

        ksort($list);

        $data = [
            'count' => count($list),
            'commit' => $commit,
            'commit_short' => substr($commit, 0, 9),
            'date' => time(),
            'files' => array_values($list)
        ];

        file_put_contents("{$folder}/map.json", json_encode($data, JSON_PRETTY_PRINT));
    }
}
