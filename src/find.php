<?php declare(strict_types=1);

function cmd_help() :void {
  // code
}

function main() :void {

  if ($_SERVER['argc'] == 1) {
    cmd_help();
    return;
  }

  $dir = $_SERVER['argv'][1];
  $option = new FindOption;

  // parse options
  for ($i = 2; $i < $_SERVER['argc']; $i++) {

    $argv = $_SERVER['argv'][$i];

    // -type
    if ($argv == '-type') {
      // next argument is required
      if (array_key_exists($i+1, $_SERVER['argv']) == false) {
        fwrite(STDERR, "ERROR value is required. {$argv}\n"); exit(1);
      }
      $argv_next = $_SERVER['argv'][$i+1];
      if ($argv_next == 'f') {
        $option->type = FindType::BLOB;
      } else if ($argv_next == 'd') {
        $option->type = FindType::TREE;
      } else {
        fwrite(STDERR, "ERROR invalid type. {$argv_next}\n"); exit(1);
      }
      $i++;
      continue;
    }

    // -totalsize
    if ($argv == '-totalsize') {
      // next argument is required
      if (array_key_exists($i+1, $_SERVER['argv']) == false) {
        fwrite(STDERR, "ERROR value is required. {$argv}\n"); exit(1);
      }
      $argv_next = $_SERVER['argv'][$i+1];
      $option->total_size = bytes2int($argv_next);
      // when -totalsize specified
      //   then print file only
      $option->type = FindType::BLOB;
      $i++;
      continue;
    }

    fwrite(STDERR, "ERROR invalid option. {$argv}\n"); exit(1);
  }

  cmd_find($dir, $option);
}

function cmd_find(string $dir, FindOption $option) :void {

  if (is_dir($dir) == false) {
    fwrite(STDERR, "ERROR dir not found.\n"); exit(1);
  }

  if (substr($dir, -1) == '/') {
    $dir = substr($dir, 0, -1);
  }

  $f = new FindHandler;
  array_push($f->dirs, $dir);

  while (true) {

    $dir = array_shift($f->dirs);
    if (is_null($dir) == true) {
      break;
    }

    if ( ! $d = opendir($dir)) {
      fwrite(STDERR, "ERROR opendir() failed. {$dir}\n"); exit(1);
    }

    while (($name = readdir($d)) !== false ) {

      if ($name == '.' || $name == '..') {
        continue;
      }

      $path = $dir.DIRECTORY_SEPARATOR.$name;

      find_path($f, $option, $path);

      if ($f->status == FindStatus::STOP) {
        closedir($d);
        return;
      }
    }

    closedir($d);
  }
}

function find_path(
    FindHandler $f
  , FindOption $option
  , string $path
  ) :void {

  if (is_dir($path) == true) {
    $file_type = FindType::TREE;
    array_push($f->dirs, $path);
  } else if (is_file($path) == true) {
    $file_type = FindType::BLOB;
  } else {
    fwrite(STDERR, "ERROR invalid type. {$path}\n"); exit(1);
  }

  // -totalsize
  if (  $option->total_size > 0
    &&  $file_type == FindType::BLOB
    ) {

    $file_size = filesize($path);
    if ($f->total_size + $file_size > $option->total_size) {
      $f->status = FindStatus::STOP;
      return;
    }

    $f->total_size += $file_size;
  }

  // -print
  if ($option->type == FindType::ALL) {
    echo("{$path}\n");
  } else if ($option->type == $file_type) {
    echo("{$path}\n");
  }
}

// Convert human bytes to integer
//
// 10 , 10K , 10M , 10G
// 10B, 10KB, 10MB, 10GB

function bytes2int(string $bytes) :int {

  $units = array(
     'B'  => 1
   , 'K'  => 1024
   , 'KB' => 1024
   , 'M'  => 1024 * 1024
   , 'MB' => 1024 * 1024
   , 'G'  => 1024 * 1024 * 1024
   , 'GB' => 1024 * 1024 * 1024
  );

  // parse number and unit
  if (array_key_exists(substr($bytes, -2), $units) == true) {
    $unit = substr($bytes, -2);
    $number = substr($bytes, 0, -2);
  } elseif (array_key_exists(substr($bytes, -1), $units) == true) {
    $unit = substr($bytes, -1);
    $number = substr($bytes, 0, -1);
  } else {
    $unit = 'B';
    $number = $bytes;
  }

  // verify number
  if (ctype_digit($number) == false) {
    fwrite(STDERR, "ERROR invalid number. {$number}\n"); exit(1);
  }

  return intval($number) * $units[$unit];
}

class FindHandler {
  public array $dirs = array();
  public int $total_size = 0;
  public FindStatus $status = FindStatus::READ;
}

class FindOption {
  public int $total_size = -1;
  public FindType $type = FindType::ALL;
}

enum FindType {
  // NOTE can not use FILE since it is already a function name
  case BLOB;
  case TREE;
  case ALL;
}

enum FindStatus {
  case READ;
  // require immediate stop.
  // eg. total file size have been reached
  case STOP;
}

main();

