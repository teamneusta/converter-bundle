#!/bin/bash
source .env
# set this to the dir where the sonarqube can persist his data:
PROJECTDIR=$(pwd)

SKIPTESTS="false"
BROWSER=default
TC_RED='\033[0;31m'
TC_ORANGE='\033[0;33m'
TC_GREEN='\033[0;32m'
TC_NO_COLOR='\033[0m'

function fatal_error() {
  echo "***********************************"
  echo -e "${TC_RED}$1${TC_NO_COLOR}\n"
  echo -e "${TC_RED}Exiting.${TC_NO_COLOR}\n"
  echo "Currently using this .env: $(pwd)/.env"
  echo "***********************************"
}

function warning() {
  echo "***********************************"
  echo -e "${TC_ORANGE}$1${TC_NO_COLOR}\n"
  echo "Currently using this .env: $(pwd)/.env"
  echo "***********************************"
}

function message() {
  echo "***********************************"
  echo -e "${TC_GREEN}$1${TC_NO_COLOR}\n"
  echo "***********************************"
}

options=$(getopt -o b --long browser: --long skiptests -- "$@")
[ $? -eq 0 ] || {
    echo "Incorrect options provided"
    exit 1
}
eval set -- "$options"
while true; do
    case "$1" in
    --browser)
	    shift;
	    BROWSER=$1
        [[ ! $BROWSER =~ CHROME|chrome|DEFAULT|default ]] && {
          echo "Incorrect Browser"
          exit 1
        }
        ;;
    --skiptests)
      SKIPTESTS="true"
      ;;
    --)
        shift
        break
        ;;
    esac
    shift
done


if [ -z "$SONARQUBE_DATA_DIR" ]; then
    fatal_error "\$SONARQUBE_DATA_DIR is empty or undefined. Please add/set it in you .env-file."
    exit
fi

if [ ! -d "$SONARQUBE_DATA_DIR" ]; then
    fatal_error "Sonarqube-Data-Dir is not present.\nPlease create: ${SONARQUBE_DATA_DIR}"
    exit
fi

if [ -z "$SONARQUBE_PORT" ]; then
    warning "\$SONARQUBE_PORT is empty or undefined.\nDefaulting now to port 9002."
    SONARQUBE_PORT=9002
fi


if [ ! "$(docker ps -q -f name=sonarqube -f status=running)" ]; then
    if [ "$(docker ps -aq -f status=exited -f name=sonarqube)" ]; then
        # cleanup
        warning "Sonarqube-container was not cleanly removed.\nRemoving it now."
        docker rm sonarqube
    fi
    # check if image with tag mysonarqube is present. Build it if not.
    if [[ "$(docker images -q mysonarqube 2> /dev/null)" == "" ]]; then
        warning "Docker-Image with tag mysonareqube not found.\nBuilding it now."
        docker build -f .docker/sonarqube/Dockerfile -t mysonarqube .docker/sonarqube
    fi
    # run your container
    message "Starting THE Qube..."
    docker run -d -p $SONARQUBE_PORT:9000 -p 9092:9092 -v $SONARQUBE_DATA_DIR:/opt/sonarqube/data --name sonarqube mysonarqube

    message "Sleep some time...(60 Seconds to wait for the Qube)"
    n=1
    echo -ne "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - \r"
    while [ $n -lt 61 ]
    do
        echo -ne '\xF0\x9F\x8C\xBE'
        sleep 1s
        ((n=$n+1))
    done
    echo " "
fi

if [ ! "$(docker ps -q -f name=sonarqube -f status=running)" ]; then
  fatal_error "Sonarqube is still not running.\nPlease check you docker stack and Sonarqube configuration."
  exit
fi


if [ "$SKIPTESTS" == "true" ]; then
    echo '--skiptests wurde angegeben. Die Unit-Tests werden nicht ausgeführt.'
else
    message "running phpunit now..."
    make tests
fi

BRANCHNAME=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
##echo 'changing Project-Name to: DSV-Website-Relaunch 2020, Branch:' $BRANCHNAME
##sed -i 's|sonar.projectName=.*|sonar.projectName=DSV-Website-Relaunch 2020, Branch: '$BRANCHNAME'|g' sonar-project.properties
#
BRANCHNAMEREPL=pimcore-designsystem-bundle
##echo 'changing Project-Key to: gvr_'$BRANCHNAMEREPL
##sed -i 's|sonar.projectKey=.*|sonar.projectKey=gvr_'$BRANCHNAMEREPL'|g' sonar-project.properties
#echo 'Changing Branch-Name to: '$BRANCHNAMEREPL
#sed -i 's|sonar.branch.name=.*|sonar.branch.name='$BRANCHNAMEREPL'|g' sonar-project.properties

message "Removing absolute Path: ${DCPROJECTDIR}"
sed -i 's|'$DCPROJECTDIR'|/usr/src/|g' .reports/clover-coverage.xml
sed -i 's|'$DCPROJECTDIR'|/usr/src/|g' .reports/junit-report.xml

message "Project will be scanned now..."
if [ "$(docker ps -aq -f status=exited -f name=sonarscanner)" ]; then
    # cleanup
    docker rm sonarscanner
fi

docker run -ti -v $(pwd):/usr/src -u root --link sonarqube --name sonarscanner sonarsource/sonar-scanner-cli -Dsonar.projectKey=pimcore-designsystem-bundle

docker rm sonarscanner

URL=http://localhost:$SONARQUBE_PORT/dashboard?id=pimcore-designsystem-bundle

if [ "${BROWSER,,}" == "chrome" ]; then
  google-chrome $URL
else
  if which xdg-open > /dev/null
  then
    xdg-open $URL
  elif which gnome-open > /dev/null
  then
    gnome-open $URL
  fi
fi
