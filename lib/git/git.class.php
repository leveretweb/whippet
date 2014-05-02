<?php

class Git {
  function __construct($repo_path) {
    $this->repo_path = $repo_path;
  }

  function is_repo() {
    return file_exists("{$this->repo_path}/.git");
  }

  function checkout($revision) {
    list($output, $return) = $this->run_command("git fetch -a && git checkout {$revision}");

    return $this->check_git_return("Checkout failed", $return, $output);
  }

  function hard_reset($revision = "HEAD") {
    list($output, $return) = $this->run_command("git reset --hard {$revision}");

    return $this->check_git_return("Reset --hard failed", $return, $output);
  }

  function mixed_reset($revision = "HEAD") {
    list($output, $return) = $this->run_command("git reset --mixed {$revision}");

    return $this->check_git_return("Reset --mixed failed", $return, $output);
  }

  function clone_repo($repository) {
    list($output, $return) = $this->run_command("git clone {$repository} {$this->repo_path}", false);

    if(!$this->check_git_return("Clone failed", $return, $output)) {
      return false;
    }

    return true;
  }

  function clone_no_checkout($repository) {
    $tmpdir = $this->get_tmpdir();

    list($output, $return) = $this->run_command("git clone --no-checkout {$repository} {$tmpdir}", false);

    if(!$this->check_git_return("No-checkout clone failed", $return, $output)) {
      return false;
    }

    $this->run_command("mv {$tmpdir}/.git {$this->repo_path}");

    return true;
  }

  function delete_repo() {
    $this->run_command("rm -rf {$this->repo_path}", false);
  }

  function current_commit() {
    list($output, $return) = $this->run_command("git rev-parse HEAD");

    if(!$this->check_git_return("Checkout failed", $return, $output)) {
      return false;
    }

    return $output[0];
  }

  function local_revision_commit($revision) {
    list($output, $return) = $this->run_command("git show-ref");

    foreach($this->parse_ref_list($output) as $ref) {
      if($ref->name == $revision) {
        return $ref->commit;
      }
    }

    return false;
  }

  function remote_revision_commit($revision) {
    list($output, $return) = $this->run_command("git ls-remote");

    foreach($this->parse_ref_list($output) as $ref) {
      if($ref->name == $revision) {
        return $ref->commit;
      }
    }

    return $this->parse_ref_list($output);
  }

  function fetch() {
    list($output, $return) = $this->run_command("git fetch -a");

    return $this->check_git_return("Checkout failed", $return, $output);
  }

  function rm($path, $rf = false) {
    list($output, $return) = $this->run_command("git rm " . ($rf ? "-rf" : "") . " {$path}");

    return $this->check_git_return("rm failed", $return, $output);
  }

  function add($path) {
    list($output, $return) = $this->run_command("git add {$path}");

    return $this->check_git_return("Add failed", $return, $output);
  }

  function commit($message) {
    list($output, $return) = $this->run_command("git commit -m '{$message}'");

    return $this->check_git_return("Checkout failed", $return, $output);
  }

  protected function parse_ref_list($reflist) {
    $refs = array();
    foreach($reflist as $line) {
      if(preg_match("/^([a-z0-9]{40})\s+(.+)$/", $line, $matches)) {
        $ref = new stdClass();

        $ref->commit = $matches[1];

        if(preg_match("#^refs/(tags|heads)/(.+)$#", $matches[2], $matches)) {
          $ref->tag = $matches[1] == 'tags';
          $ref->branch = $matches[1] == 'branch';
          $ref->name = $matches[2];

          $refs[] = $ref;
        }
      }
    }

    return $refs;
  }

  protected function check_git_return($message, $return, $output) {
    if($return !== 0) {
      echo "{$message}:\n\n" . implode("\n", $output);

      return false;
    }

    return true;
  }

  protected function run_command($command, $cd = true) {
    $output = array();
    $return = 0;

    if($cd && !file_exists($this->repo_path)) {
      echo "Error: directory does not exist ({$this->repo_path})\n";
      exit(1);
    }

    if($cd) {
      $cd = "cd {$this->repo_path} && ";
    }
    else {
      $cd = '';
    }

    exec("{$cd}{$command}", $output, $return);
    // echo ("{$cd}{$command}\n");

    return array($output, $return);
  }

  function get_tmpdir($in_dir = false) {

    if(!$in_dir) {
      $in_dir = sys_get_temp_dir();
    }

    do {
      $tmp_dir = $in_dir . "/" . md5(microtime());
    } while(file_exists($tmp_dir));

    return $tmp_dir;
  }
};