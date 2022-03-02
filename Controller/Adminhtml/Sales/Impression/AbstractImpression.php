<?php
namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Filesystem\DirectoryList;

use Chronopost\Chronorelais\Helper\Data as HelperData;

Abstract class AbstractImpression extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * AbstractImpression constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData
    ) {
        $this->_directoryList = $directoryList;
        $this->resultPageFactory = $resultPageFactory;
        $this->_helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }

    /**
     * @param $fileName
     * @param $content
     * @param string $contentType
     * @param null $contentLength
     * @return $this
     */
    public function prepareDownloadResponse($fileName,$content,$contentType = 'application/octet-stream',$contentLength = null)
    {
        $isFile = false;
        $file   = null;
        if (is_array($content)) {
            if (!isset($content['type']) || !isset($content['value'])) {
                return $this;
            }
            if ($content['type'] == 'filename') {
                $isFile         = true;
                $file           = $content['value'];
                $contentLength  = filesize($file);
            }
        }

       $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', is_null($contentLength) ? strlen($content) : $contentLength, true)
            ->setHeader('Content-Disposition', 'attachment; filename="'.$fileName.'"', true)
            ->setHeader('Last-Modified', date('r'), true);

        if (!is_null($content)) {
            if ($isFile) {
                /*$this->getResponse()->clearBody();
                $this->getResponse()->sendHeaders();

                $ioAdapter = new \Varien_Io_File();
                $ioAdapter->open(array('path' => $ioAdapter->dirname($file)));
                $ioAdapter->streamOpen($file, 'r');
                while ($buffer = $ioAdapter->streamRead()) {
                    print $buffer;
                }
                $ioAdapter->streamClose();
                if (!empty($content['rm'])) {
                    $ioAdapter->rm($file);
                }

                exit(0);*/

                $content = file_get_contents($file);

            } /*else {
                $this->getResponse()->setBody($content);
            }*/
            $this->getResponse()->setBody($content);
        }
        return $this;
    }

    /**
     * @param $pdf_contents
     * @return mixed
     */
    public function _processDownloadMass($pdf_contents) {

        $paths = array();
        $this->createMediaChronopostFolder();
        $indiceFile = 0;
        foreach ($pdf_contents as $pdf_content) {
            $fileName = 'tmp-etiquette-'.date('H-i-s-'.$indiceFile);
            /* save pdf file */
            $path = $this->_directoryList->getPath('media').'/chronopost/' . $fileName . '.pdf';
            file_put_contents($path, $pdf_content);
            $paths[] = $path;
            $indiceFile++;
        }

        /* creation d'un pdf unique */
        $pdfMergeFileName = "merged-".date('YmdHis').".pdf";
        $pathMerge = $this->_directoryList->getPath('media')."/chronopost/".$pdfMergeFileName;
        $cmd = $this->_helperData->getConfig("chronorelais/shipping/gs_path") .' -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile="'.$pathMerge.'" -dBATCH '. implode(' ', $paths);
        $res_shell = shell_exec($cmd);
        /* suppression des pdf temp */
        foreach ($paths as $path) {
            if(is_file($path)) {
                unlink($path);
            }
        }

        if ($res_shell === null) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath("chronopost_chronorelais/sales/impression");
            return $resultRedirect;
        }
        else {
            $this->prepareDownloadResponse($pdfMergeFileName,file_get_contents($pathMerge));
            unlink($pathMerge);
        }
    }

    /* create folder media/chronopost if not exist */
    protected function createMediaChronopostFolder() {
        $path = $this->_directoryList->getPath('media').'/chronopost';
        if(!is_dir($path)) {
            mkdir($path,0777);
        }
    }

    /**
     * Verifie si ghostscript est installé
     * @return bool
     */
    public function gsIsActive() {
        $cmdTestGs = $this->_helperData->getConfig("chronorelais/shipping/gs_path") ." -v";
        return shell_exec($cmdTestGs) !== null;
    }


}