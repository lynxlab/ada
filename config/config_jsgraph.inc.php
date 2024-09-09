<?php
/******************************************************
// Standard configuration file for ADA versione 1.8
// Copyright Lynx 2006
// Released under GPL GNU license
// *****************************************************/

// First find out where we are executed from
//define('DIR_BASE',"/home/ljp/www/jpgraph/dev/");
define('DIR_BASE', ADA_UPLOAD_PATH . 'tmp/');

// If the color palette is full should JpGraph try to allocate
// the closest match? If you plan on using background image or
// gradient fills it might be a good idea to enable this.
// If not you will otherwise get an error saying that the color palette is
// exhausted. The drawback of using approximations is that the colors
// might not be exactly what you specified.
define('USE_APPROX_COLORS', true);

// Should usage of deprecated functions and parameters give a fatal error?
// (Useful to check if code is future proof.)
define('ERR_DEPRECATED', true);

// Should we try to read from the cache? Set to false to bypass the
// reading of the cache and always re-generate the image and save it in
// the cache. Useful for debugging.
define('READ_CACHE', false);

// The full name of directory to be used as a cache. This directory MUST
// be readable and writable for PHP. Must end with '/'
define('CACHE_DIR', DIR_BASE.'jpgraph_cache/');

// Directory for TTF fonts. Must end with '/'
define('TTF_DIR', DIR_BASE.'ttf/');
