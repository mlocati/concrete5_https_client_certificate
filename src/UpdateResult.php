<?php

namespace HttpsClientCertificate;

use DateTime;

class UpdateResult
{
    /**
     * Has the file been updated?
     *
     * @var bool
     */
    protected $updated;

    /**
     * The file name.
     *
     * @var string
     */
    protected $filename;

    /**
     * Date/time when the file will be updated.
     *
     * @var DateTime|null
     */
    protected $nextUpdate;

    /**
     * Has the file been updated?
     *
     * @return bool
     */
    public function isUpdated()
    {
        return $this->updated;
    }

    /**
     * The file name.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Date/time when the file will be updated (if it's already up-to-date).
     *
     * @return DateTime|null
     */
    public function getNextUpdate()
    {
        return $this->nextUpdate;
    }

    /**
     * Initialize the instance.
     */
    protected function __construct()
    {
        $this->updated = false;
        $this->shownFilename = '';
        $this->nextUpdate = null;
    }

    /**
     * Create new class instance when the file has been updated.
     *
     * @param string $filename The file name
     *
     * @return static
     */
    public static function updated($filename, DateTime $nextUpdate)
    {
        $result = new static();
        $result->updated = true;
        $result->filename = $filename;
        $result->nextUpdate = $nextUpdate;

        return $result;
    }

    /**
     * Create new class instance when the file is already up-to-date.
     *
     * @param string $filename The file name
     * @param DateTime $nextUpdate When will the file be updated?
     *
     * @return static
     */
    public static function alreadyUpToDate($filename, DateTime $nextUpdate)
    {
        $result = new static();
        $result->filename = $filename;
        $result->nextUpdate = $nextUpdate;

        return $result;
    }
}
