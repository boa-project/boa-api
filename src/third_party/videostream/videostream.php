<?php
/**
 * Based in VideoStream class, original by Rana
 * https://gist.github.com/ranacseruet/9826293
 *
 * @author Rana
 * @link http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 */
class VideoStream
{
    private $path = "";
    private $stream = "";
    private $buffer = 102400;
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;
    private $videoFormats = array("mp4"=>"video/mp4", "webm"=>"video/webm", "ogg"=>"video/ogg");

    function __construct($filePath)
    {
        $this->path = $filePath;
    }

    /**
     * Open stream
     */
    private function open()
    {
        if (!($this->stream = fopen($this->path, 'rb'))) {
            die('Could not open stream for reading');
        }

    }

    /**
     * Open stream
     * You can have s3 service’s file location support on the above class very easily. First,
     * make sure to register ‘streamWrapper’ with your s3 client:
     *
     * <code>$s3Client->registerStreamWrapper();</code>
     *
     * then, while passing file path to VideoStream class’s constructor, use “s3://{bucket}/{key}”
     * format as file string.
     */
    private function openFromS3()
    {
        // Create a stream context to allow seeking
        $context = stream_context_create(array(
            's3' => array(
                'seekable' => true
            )
        ));
        if (!($this->stream = fopen($this->path, 'rb', false, $context))) {
            die('Could not open stream for reading');
        }

    }

    /**
     * Set proper header to serve the video content
     */
    private function setHeader()
    {
        ob_get_clean();

        $ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        $contenttype = isset($ext, $this->videoFormats) ? $this->videoFormats[$ext] : $this->videoFormats['mp4'];
        header("Content-Type: " . $contenttype);
        //header("Content-Type: ".mime_content_type($this->path));

        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;
        header("Accept-Ranges: bytes 0-".$this->end);
        //header("Accept-Ranges: 0-".$this->end);

        if (isset($_SERVER['HTTP_RANGE'])) {

            $c_start = $this->start;
            $c_end = $this->end;

            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            }else{
                $range = explode('-', $range);
                $c_start = $range[0];

                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$this->size);
        }
        else
        {
            header("Content-Length: ".$this->size);
        }

    }

    /**
     * close curretly opened stream
     */
    private function end()
    {
        fclose($this->stream);
        exit;
    }

    /**
     * Perform the streaming of calculated range
     */
    private function stream()
    {
        $i = $this->start;
        set_time_limit(0);
        while(!feof($this->stream) && $i <= $this->end && !connection_aborted()) {
            $bytesToRead = $this->buffer;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = fread($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }

    /**
     * Start streaming video content
     */
    function start($s3 = false)
    {
        if ($s3) {
            $this->openFromS3();
        }
        else {
            $this->open();
        }

        $this->setHeader();
        $this->stream();
        $this->end();
    }
}