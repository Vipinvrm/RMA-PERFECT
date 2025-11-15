<?php
namespace Krishaweb\Rma\Controller\Rma;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
{
    protected $uploaderFactory;
    protected $filesystem;
    protected $customerSession;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        // Check if customer is logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $resultJson->setData([
                'error' => true,
                'message' => __('You must be logged in to upload images.')
            ]);
        }

        try {
            // Check if file was uploaded
            if (!isset($_FILES['rma_image']) || !isset($_FILES['rma_image']['name'])) {
                throw new LocalizedException(__('No file uploaded.'));
            }

            // Get the uploader
            $uploader = $this->uploaderFactory->create(['fileId' => 'rma_image']);
            
            // Set allowed extensions and validate
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // Validate file size (max 5MB)
            $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
            if ($_FILES['rma_image']['size'] > $maxFileSize) {
                throw new LocalizedException(__('File size exceeds maximum allowed size of 5MB.'));
            }

            // Get media directory
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $rmaImagePath = $mediaDirectory->getAbsolutePath('rma/images');

            // Create directory if it doesn't exist
            if (!file_exists($rmaImagePath)) {
                mkdir($rmaImagePath, 0777, true);
            }

            // Upload file
            $result = $uploader->save($rmaImagePath);

            if (!$result) {
                throw new LocalizedException(__('File upload failed.'));
            }

            // Get the uploaded file path relative to media directory
            $imagePath = 'rma/images/' . $result['file'];
            $imageUrl = $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $imagePath;

            return $resultJson->setData([
                'error' => false,
                'name' => $result['file'],
                'path' => $imagePath,
                'url' => $imageUrl,
                'message' => __('Image uploaded successfully.')
            ]);

        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => true,
                'message' => __('An error occurred while uploading the image: %1', $e->getMessage())
            ]);
        }
    }
}