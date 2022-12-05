<?php
/**
 * Image resize and compress class
 * Simple PHP class for resizing and compressing images in PNG, JPEG and GIF format.
 * @uses PHP GD extension.
 * @see ImageUpload()
 *
 * Can use PNGQuant:
 * @see $PNGQuantPath optional configuration variable.
 *
 * @since 3.3
 *
 * @package RosarioSIS
 * @subpackage classes
 */


/*!
 * hi@j0hn.dk
 * No copyrights. Feel free to use this the way you like.
 *
 * @link https://github.com/michaube/image-resize-gd
 */
class ImageResizeGD {

	/**
	 * @var resource
	 */
	private $image;
	/**
	 * @var resource
	 */
	private $imageModified;

	/**
	 * @var int
	 */
	private $sourceWidth;
	/**
	 * @var int
	 */
	private $sourceHeight;

	/**
	 * @var int
	 */
	private $sourceType;

	/**
	 * @var int
	 */
	private $newWidth;
	/**
	 * @var int
	 */
	private $newHeight;

	/**
	 * @var int
	 */
	private $defaultJPEGCompression;

	/**
	 * @var int
	 */
	private $defaultPNGCompression;

	/**
	 * @var string
	 */
	private $PNGQuantPath;

	/**
	 * @var array
	 */
	private $allowedImageTypes = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG);


	public function __destruct() {
		if ( is_resource( $this->image ) ) {
			// We don't need the original in memory anymore.
			imagedestroy( $this->image );
		}
	}


	/**
	 * Checks to see if current environment supports GD.
	 *
	 * @author Wordpress
	 * @see class-wp-image-editor-gd.php file.
	 *
	 * @static
	 * @access public
	 *
	 * @return bool
	 */
	public static function test() {
		if ( ! extension_loaded('gd') || ! function_exists('gd_info') )
			return false;

		return true;
	}

	/**
	 * Image resizing and compressing utility.
	 *
	 * @author hi@j0hn.dk
	 * @author François Jacquet
	 *
	 * @param string $imagePathOrString Path to the source image or base64 string image.
	 * @param int    $defaultJPEGCompression integer <0; 100>
	 * @param int    $defaultPNGCompression integer <0; 9>
	 * @param string $PNGQuantPath PNGQuant path, for PNG compression.
	 *
	 */
	function __construct($imagePathOrString, $defaultJPEGCompression = 80, $defaultPNGCompression = 9, $PNGQuantPath = '') {

		if ( strpos( $imagePathOrString, 'data:image' ) === 0 ) {

			$this->image = $this->openBase64Image( $imagePathOrString );
		} else {

			$this->image = $this->openImageFile( $imagePathOrString );
		}

		$this->setDefaultJPEGCompression($defaultJPEGCompression);
		$this->setDefaultPNGCompression($defaultPNGCompression);
		$this->setPNGQuantPath($PNGQuantPath);

		$this->sourceWidth = imagesx($this->image);
		$this->sourceHeight = imagesy($this->image);

		if($this->sourceWidth === false || $this->sourceHeight === false) {
			throw new \InvalidArgumentException('Image type is not supported or file is corrupted.');
		}
	}

	/**
	 * Resize image, so the output does not exceed given width and height.
	 * Smaller image will be upscaled.
	 * Method maintains the aspect ratio.
	 *
	 * @param int $newWidth Desired width of the output image
	 * @param int $newHeight Desired height of the output image
	 * @return void
	 */
	public function resizeWithinDimensions($newWidth, $newHeight) {
		if ($newWidth === $this->sourceWidth && $newHeight === $this->sourceHeight) {
			$this->copyImageDataWithoutResampling();
			$this->newWidth = $this->sourceWidth;
			$this->newHeight = $this->sourceHeight;
			return;
		}
		$widthRatio  = $this->sourceWidth / $newWidth;
		$heightRatio = $this->sourceHeight / $newHeight;

		if ($widthRatio > $heightRatio) {
			$this->resizeByWidth($newWidth);
		} else {
			$this->resizeByHeight($newHeight);
		}
	}

	/**
	 * Resize image, so the output gets exactly given width.
	 * Height will be scaled maintaining the aspect ratio.
	 *
	 * @param int $newWidth Desired width of the output image
	 * @return void
	 */
	public function resizeByWidth($newWidth) {
		if ($newWidth === $this->sourceWidth) {
			$this->copyImageDataWithoutResampling();
			$this->newWidth = $this->sourceWidth;
			$this->newHeight = $this->sourceHeight;
			return;
		}
		$this->newWidth = $newWidth;

		$ratio = $this->sourceHeight / $this->sourceWidth;
		$this->newHeight = $ratio * $newWidth;

		$this->copyImageData();
	}

	/**
	 * Resize image, so the output gets exactly given height.
	 * Width will be scaled maintaining the aspect ratio.
	 *
	 * @param int $newHeight Desired height of the output image
	 * @return void
	 */
	public function resizeByHeight($newHeight) {
		if ($newHeight === $this->sourceHeight) {
			$this->copyImageDataWithoutResampling();
			$this->newWidth = $this->sourceWidth;
			$this->newHeight = $this->sourceHeight;
			return;
		}
		$this->newHeight = $newHeight;

		$ratio = $this->sourceWidth / $this->sourceHeight;
		$this->newWidth = $ratio * $newHeight;

		$this->copyImageData();
	}

	/**
	 * Resize image, so the output matches exactly given dimensions.
	 * Image is scaled, centered and cropped.
	 *
	 * @param int $newWidth Desired width of the output image
	 * @param int $newHeight Desired height of the output image
	 * @return void
	 */
	public function resizeToFillDimensionsExactly($newWidth, $newHeight) {
		if ($newWidth === $this->sourceWidth && $newHeight === $this->sourceHeight) {
			$this->copyImageDataWithoutResampling();
			$this->newWidth = $this->sourceWidth;
			$this->newHeight = $this->sourceHeight;
			return;
		}
		$widthRatio  = $this->sourceWidth / $newWidth;
		$heightRatio = $this->sourceHeight / $newHeight;

		if ($heightRatio < $widthRatio) {
			$optimalRatio = $heightRatio;
		} else {
			$optimalRatio = $widthRatio;
		}

		$this->newWidth  = $this->sourceWidth / $optimalRatio;
		$this->newHeight = $this->sourceHeight / $optimalRatio;

		$this->copyImageData();

		$cropStartX = ($this->newWidth / 2) - ($newWidth / 2);
		$cropStartY = ($this->newHeight / 2) - ($newHeight / 2);

		$imageCropped = $this->imageModified;

		$this->imageModified = imagecreatetruecolor((int) $newWidth , (int) $newHeight);

		imagecopy($this->imageModified, $imageCropped , 0, 0, $cropStartX, $cropStartY, (int) $newWidth, (int) $newHeight);

		$this->newWidth = $newWidth;
		$this->newHeight = $newHeight;
	}

	/**
	 * Saves image after modifications, or a copy if no modifications were done.
	 * Allows adding a solid color background to transparent images.
	 * By default output will be saved as the same type as source.
	 * By default, the name of the output file is null:
	 * the image will be output to browser.
	 *
	 * @param string $saveImageName Name of the output file. Do not use extension here, it will be added based on $extension parameter.
	 * @param int $quality Should be value <0; 100> for IMAGETYPE_JPEG, <0; 9> for IMAGETYPE_PNG
	 * @param int $extension Desired output format. Should be one of php predefined constants: IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
	 * @param string|null $backgroundColor Should be a hexadecimal RGB color value without hash, like FF0000 or 090909
	 * @return string Name of saved output file with extension. For example foo.jpg
	 * @throws Exception When installed gd library does not support desired $extension
	 * @throws Exception When image could not be saved
	 * @throws InvalidArgumentException When $extension is not a value of any of those constants: IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
	 *
	 */
	public function saveImageFile($saveImageName = null, $extension = null, $quality = null, $backgroundColor = null) {
		if($this->imageModified === null) {
			$this->copyImageDataWithoutResampling();
		}

		if($extension !== null) {
			if(!in_array($extension, $this->allowedImageTypes)) {
				throw new \InvalidArgumentException('This image type is not supported.');
			}
		} else {
			$extension = $this->sourceType;
		}

		if($backgroundColor !== null) {
			$this->addBackgroundColor($backgroundColor);
		}

		if($quality < 0) {
			$quality = 0;
		}

		if($quality > 100 && $extension === IMAGETYPE_JPEG) {
			$quality = 100;
		}

		if($quality > 9 && $extension === IMAGETYPE_PNG) {
			$quality = 9;
		}

		switch($extension) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {

					$imageFile = is_null( $saveImageName ) ? null :
						$saveImageName . ( substr( $saveImageName, -4 ) === '.gif' ? '' : '.gif' );

					$imageResult = imagegif($this->imageModified, $imageFile);
				} else {
					throw new \Exception('Your GD library does not support gif image types.');
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					if($quality === null) {
						$quality = $this->getDefaultJPEGCompression();
					}

					$imageFile = is_null( $saveImageName ) ? null :
						$saveImageName . ( substr( $saveImageName, -4 ) === '.jpg' ? '' :
							( substr( $saveImageName, -5 ) === '.jpeg' ? '' : '.jpg' ) );

					$imageResult = imagejpeg($this->imageModified, $imageFile, $quality);
				} else {
					throw new \Exception('Your GD library does not support jpg image types.');
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					if($quality === null) {
						$quality = $this->getDefaultPNGCompression();
					}

					$imageFile = is_null( $saveImageName ) ? null :
						$saveImageName . ( substr( $saveImageName, -4 ) === '.png' ? '' : '.png' );

					$imageResult = imagepng($this->imageModified, $imageFile, $quality);

					$this->compressPNGQuant( $imageFile );
				} else {
					throw new \Exception('Your GD library does not support png image types.');
				}
				break;
			default:
				break;
		}

		if($imageResult === false) {
			throw new \Exception('Image could not be saved.');
		}

		imagedestroy($this->imageModified);
		$this->imageModified = null;

		return $imageFile;
	}

	/**
	 * @return int
	 */
	public function getDefaultJPEGCompression()
	{
		return $this->defaultJPEGCompression;
	}

	/**
	 * @param int $defaultJPEGCompression
	 */
	public function setDefaultJPEGCompression($defaultJPEGCompression)
	{
		$this->defaultJPEGCompression = $defaultJPEGCompression;
	}

	/**
	 * @return int
	 */
	public function getDefaultPNGCompression()
	{
		return $this->defaultPNGCompression;
	}

	/**
	 * @param int $defaultPNGCompression
	 */
	public function setDefaultPNGCompression($defaultPNGCompression)
	{
		$this->defaultPNGCompression = $defaultPNGCompression;
	}

	/**
	 * @return string
	 */
	public function getPNGQuantPath()
	{
		return $this->PNGQuantPath;
	}

	/**
	 * @param string $PNGQuantPath
	 */
	public function setPNGQuantPath($PNGQuantPath)
	{
		$this->PNGQuantPath = (string) $PNGQuantPath;
	}

	/**
	 * @return string
	 */
	public function getSourceType()
	{
		return $this->sourceType;
	}

	/**
	 * @return integer
	 */
	public function getSourceWidth()
	{
		return $this->sourceWidth;
	}

	/**
	 * @return integer
	 */
	public function getSourceHeight()
	{
		return $this->sourceHeight;
	}

	/**
	 * Adds solid background to image
	 *
	 * @param $backgroundColor
	 * @return void
	 */
	protected function addBackgroundColor($backgroundColor) {
		if(strlen($backgroundColor) !== 6) {
			throw new \InvalidArgumentException('Argument has to be a hexadecimal RGB color value, without hash. For example FFFFFF.');
		}

		$decRed = hexdec(substr($backgroundColor, 0, 2));
		$decGreen = hexdec(substr($backgroundColor, 2, 2));
		$decBlue = hexdec(substr($backgroundColor, 4, 2));

		$imageSolidBackground = imagecreatetruecolor((int) $this->newWidth, (int) $this->newHeight);
		$solidColor = imagecolorallocate($imageSolidBackground, $decRed, $decGreen, $decBlue);
		imagefilledrectangle($imageSolidBackground, 0, 0, (int) $this->newWidth, (int) $this->newHeight, $solidColor);
		imagecopy($imageSolidBackground, $this->imageModified, 0, 0, 0, 0, (int) $this->newWidth, (int) $this->newHeight);

		$this->imageModified = $imageSolidBackground;
	}

	/**
	 * @return void
	 */
	protected function copyImageDataWithoutResampling() {
		$this->imageModified = $this->image;

		$this->newWidth = $this->sourceWidth;
		$this->newHeight = $this->sourceHeight;
	}

	/**
	 * @return void
	 */
	protected function copyImageData() {
		$this->imageModified = imagecreatetruecolor((int) $this->newWidth, (int) $this->newHeight);

		imagealphablending($this->imageModified, false);
		imagesavealpha($this->imageModified, true);

		imagecopyresampled($this->imageModified, $this->image, 0, 0, 0, 0, (int) $this->newWidth, (int) $this->newHeight, $this->sourceWidth, $this->sourceHeight);
	}

	/**
	 * @param string $imagePath
	 * @return resource
	 * @throws InvalidArgumentException Image type is not supported or file is corrupted
	 * @throws InvalidArgumentException Image type is not any of those IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
	 * @throws Exception Image could not be opened
	 */
	protected function openImageFile($imagePath) {

		if ( ! function_exists( 'exif_imagetype' ) ) {
			/**
			 * Exif imagetype function
			 * Fix #171 Adding photos to users and students
			 * Provides function if PHP exif extension not installed.
			 *
			 * @since 3.5.2
			 *
			 * @link http://php.net/manual/en/function.exif-imagetype.php#80383
			 *
			 * @param  string $filename File name.
			 * @return mixed            Image type integer or false.
			 */
			function exif_imagetype ( $filename ) {
				if ( ( list($width, $height, $type, $attr) = getimagesize( $filename ) ) !== false ) {
					return $type;
				}

				return false;
			}
		}

		$imageType = @exif_imagetype($imagePath);

		if ($imageType === false) {
			throw new \InvalidArgumentException('Image type is not supported or file is corrupted.');
		}

		if (!$imageType || !in_array($imageType, $this->allowedImageTypes)) {
			// throw new \InvalidArgumentException('Image type is not supported.');
			// Default image type to PNG.
			$this->sourceType = IMAGETYPE_PNG;
		} else {
			$this->sourceType = $imageType;
		}

		$image = @imagecreatefromstring(file_get_contents($imagePath));

		if($image === false) {
			throw new \Exception('Image could not be opened.');
		}

		$image = $this->fixTransparentBackground( $image );

		$image = $this->fixRotation( $image, $imagePath );

		return $image;
	}


	/**
	 * Open & check base64 encoded images.
	 *
	 * @author François Jacquet
	 *
	 * @param  string $imageData Base64 encoded image, src tag.
	 *
	 * @return resource
	 */
	protected function openBase64Image( $imageData )
	{
		$imageMimeType = '';

		if ( strpos( $imageData, 'data:image' ) === 0 ) {
			$imageMimeType = substr(
				$imageData,
				strpos( $imageData, 'data:' ) + 5,
				strpos( $imageData, ';base64' ) - 5
			);

			$imageData = substr( $imageData, ( strpos( $imageData, 'base64' ) + 6 ) );
		}

		$decodedData = base64_decode( $imageData );

		$allowedImageMimeTypes = array_map( 'image_type_to_mime_type', $this->allowedImageTypes );

		if ( ! in_array( $imageMimeType, $allowedImageMimeTypes ) ) {
			if ( function_exists( 'getimagesizefromstring' ) )
			{
				$size = getimagesizefromstring( $decodedData );

				if ( ! $size
					|| $size[0] == 0
					|| $size[1] == 0
					|| ! $size['mime'] )
				{
					throw new \InvalidArgumentException('Image type is not supported or file is corrupted.');
				}

				$imageMimeType = $size['mime'];
			}

			if (!in_array($imageMimeType, $allowedImageMimeTypes)) {
				// throw new \InvalidArgumentException('Image type is not supported.');
			}
		}

		foreach ( (array) $this->allowedImageTypes as $allowedImageType ) {
			if ( image_type_to_mime_type( $allowedImageType ) === $imageMimeType ) {
				$this->sourceType = $allowedImageType;
				break;
			}
		}

		if ( ! $this->getSourceType() ) {
			// Default image type to PNG.
			$this->sourceType = IMAGETYPE_PNG;
		}

		$image = @imagecreatefromstring( $decodedData );

		if ($image === false) {
			throw new \Exception('Image type is not supported or file is corrupted.');
		}

		$image = $this->fixTransparentBackground( $image );

		return $image;
	}


	/**
	 * Fix GD bug with transparent background PNG
	 * ending up having a black background
	 * when opened with imagecreatefromstring and then saved.
	 *
	 * @link http://stackoverflow.com/questions/2611852/imagecreatefrompng-makes-a-black-background-instead-of-transparent?rq=1
	 *
	 * @param  resource $image GD image resource.
	 * @return resource        GD image resource.
	 */
	protected function fixTransparentBackground( $image )
	{
		switch ( $this->sourceType ) {
			case IMAGETYPE_GIF:
			case IMAGETYPE_PNG:
				// Integer representation of the color black (rgb: 0,0,0).
				$background = imagecolorallocate($image , 0, 0, 0);
				// Removing the black from the placeholder.
				imagecolortransparent($image, $background);

			case IMAGETYPE_PNG:
				/**
				 * Turning off alpha blending (to ensure alpha channel information
				 * is preserved, rather than removed (blending with the rest of the
				 * image in the form of black))
				 */
				imagealphablending($image, false);

				/**
				 * turning on alpha channel information saving (to ensure the full range
				 * of transparency is preserved)
				 */
				imagesavealpha($image, true);
			break;
		}

		return $image;
	}


	/**
	 * Fix JPG image rotation
	 * Some cameras store the image orientation in the EXIF data.
	 * Prevent a portrait photo to be rotated to landscape when saved
	 *
	 * @since 10.6
	 *
	 * @link https://www.php.net/manual/en/function.exif-read-data.php#110894
	 * @link https://stackoverflow.com/questions/12774411/php-resizing-image-on-upload-rotates-the-image-when-i-dont-want-it-to
	 *
	 * @param  resource $image     GD image resource.
	 * @param  string   $imagePath Path to image file.
	 * @return resource            GD image resource.
	 */
	protected function fixRotation( $image, $imagePath )
	{
		if ( $this->getSourceType() !== IMAGETYPE_JPEG )
		{
			// Only fix rotation for JPG.
			return $image;
		}

		$orientation = 0;

		if ( function_exists( 'exif_read_data' ) )
		{
			// Suppress warning Incorrect APP1 Exif Identifier Code.
			$exif = @exif_read_data( $imagePath );

			if ( isset( $exif['Orientation'] ) )
			{
				$orientation = $exif['Orientation'];
			}
		}
		elseif ( preg_match(
			'@\x12\x01\x03\x00\x01\x00\x00\x00(.)\x00\x00\x00@',
			file_get_contents( $imagePath ),
			$matches ) )
		{
			$orientation = ord( $matches[1] );
		}

		switch ( $orientation )
		{
			case 8:
				$image = imagerotate( $image, 90, 0 );
				break;
			case 3:
				$image = imagerotate( $image, 180, 0 );
				break;
			case 6:
				$image = imagerotate( $image, -90, 0 );
				break;
		}

		return $image;
	}


	/**
	 * Compress a PNG image using PNGQuant
	 * Overwrites the original file.
	 *
	 * @author François Jacquet
	 *
	 * @link https://pngquant.org/
	 *
	 * @uses shell_exec
	 *
	 * @param  string $imageFile Image file path.
	 */
	public function compressPNGQuant( $imageFile )
	{
		if ( ! $this->getPNGQuantPath() )
		{
			return;
		}

		if ( ! file_exists( $imageFile ) )
		{
			throw new InvalidArgumentException( 'File does not exist: ' . $imageFile );
		}

		if ( in_array( 'shell_exec', explode( ',', ini_get( 'disable_functions' ) ) ) )
		{
			return;
		}

		shell_exec( escapeshellarg( $this->getPNGQuantPath() ) .
			' --quality=65-95 --force --ext .png --speed 5 ' . escapeshellarg( $imageFile ) );
	}
}
