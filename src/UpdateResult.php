<?php
namespace HttpsClientCertificate;

use Concrete\Core\Support\Facade\Application;
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
     * Current file date/time.
     *
     * @var DateTime|null
     */
    protected $currentDate;

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
     * Get the current file date/time.
     *
     * @return DateTime|null
     */
    public function getCurrentDate()
    {
        return $this->currentDate;
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
        $this->currentDate = null;
        $this->nextUpdate = null;
    }

    /**
     * Create new class instance when the file has been updated.
     *
     * @param string $filename The file name
     *
     * @return static
     */
    public static function updated($filename, DateTime $nextUpdate, DateTime $currentDate = null)
    {
        $result = new static();
        $result->updated = true;
        $result->filename = $filename;
        $result->currentDate = $currentDate;
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
    public static function alreadyUpToDate($filename, DateTime $currentDate, DateTime $nextUpdate)
    {
        $result = new static();
        $result->filename = $filename;
        $result->currentDate = $currentDate;
        $result->nextUpdate = $nextUpdate;

        return $result;
    }

    /**
     * Returns a string representation of this instance.
     */
    public function __toString()
    {
        $app = Application::getFacadeApplication();
        $dateHelper = $app->make('date');
        if ($this->isUpdated()) {
            $result = t('The HTTPS Client certificate has been updated.');
            $dt = $this->getCurrentDate();
            if ($dt !== null) {
                $result .= "\n" . t('The file has been previously created on %s', $dateHelper->formatDateTime($dt, true, true));
            }
            $dt = $this->getNextUpdate();
            if ($dt !== null) {
                $result .= "\n" . t('The file will be updated again after %s', $dateHelper->formatDateTime($dt, true, true));
            }
        } else {
            $result = t('The HTTPS Client certificate is already up-to-date.');
            $dt = $this->getCurrentDate();
            if ($dt !== null) {
                $result .= "\n" . t('The file was created on %s', $dateHelper->formatDateTime($dt, true, true));
            }
            $dt = $this->getNextUpdate();
            if ($dt !== null) {
                $result .= "\n" . t('The file will be updated after %s', $dateHelper->formatDateTime($dt, true, true));
            }
        }

        return $result;
    }
}
