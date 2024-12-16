#!/bin/bash

# Function to display the confirmation prompt
function confirm() {
    while true; do
        read -p "Do you want to proceed? (YES/NO) " yn
        case $yn in
            [Yy]* ) return 0;;
            [Nn]* ) return 1;;
            * ) echo "Please answer YES or NO.";;
        esac
    done
}

# Function to check if a command exists
function check() {
  which $1 >/dev/null 2>&1
  ret=$?
  return $ret;
}

WHOAMI=`whoami`
APACHE_USER=`ps -ef | grep -E '(httpd|apache2|apache)' | grep -v $WHOAMI | grep -v root | head -n1 | awk '{print $1}'`
DIRS=("upload_file" "log" "services/media" "docs" "js" "config" "modules")

if [ -d "clients" ] && [ -f "config_path.inc.php" ]; then
  INSTALLED=1
else
  INSTALLED=0
fi

printf "\n*******************************************\n"
printf "*  This is the ADA permission fix script  *\n"
printf "*******************************************\n\n"
printf "The following commands are about to be run:\n\n"

echo "  chown $WHOAMI:$APACHE_USER ."
echo "  chmod 775 ."
if check "chcon" ; then
  echo "  chcon -t httpd_sys_rw_content_t ."
fi

for d in "${DIRS[@]}"
do
  if [ -d "$d" ]; then
    echo "  chown -R $WHOAMI:$APACHE_USER $d"
    echo "  find $d -type d -exec chmod 775 {} \;"
    echo "  find $d -type f -exec chmod 664 {} \; "
    if check "chcon" ; then
      echo "  chcon -R -t httpd_sys_rw_content_t $d"
    fi
    echo
  fi
done

if confirm; then
  chown $WHOAMI:$APACHE_USER .
  chmod 775 .
  if check "chcon" ; then
    chcon -t httpd_sys_rw_content_t .
  fi
  for d in "${DIRS[@]}"
  do
    if [ -d "$d" ]; then
      chown -R $WHOAMI:$APACHE_USER $d
      find $d -type d -exec chmod 775 {} \;
      find $d -type f -exec chmod 664 {} \;
      if check "chcon" ; then
          chcon -R -t httpd_sys_rw_content_t $d
      fi
    fi
  done
  printf "\nAll done!\n"
else
  printf "\nBye!\n"
fi
