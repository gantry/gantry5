#!/bin/bash
GIT_SOURCE=${PWD}
GIT_TARGET=${GIT_SOURCE}/themes/nucleus
OPT_DELETE=0

echo
echo "Initialize Gantry Platforms"
echo "GIT repository: ${GIT_SOURCE}"
echo

while getopts ":d" optname
do
    case "$optname" in
        "d")
            OPT_DELETE=1
            ;;
        "?")
            echo "Unknown option ${OPTARG}"
            exit 1
            ;;

    esac
done

sources=(
    "themes/nucleus/common"
    "src"
    "vendor"
)
targets=(
    "joomla"
    "wordpress"
    "grav"
    "standalone"
)

for (( t = 0 ; t < ${#targets[@]} ; t++ ))
do
    target="${GIT_TARGET}/${targets[$t]}"

    for (( i = 0 ; i < ${#sources[@]} ; i++ ))
    do
        source="$GIT_SOURCE/${sources[$i]}"
        targetFile="${target}/${source##*/}"

        if [ ! -L $targetFile ]; then
            rm -rf "$targetFile"
        else
            unlink "$targetFile"
        fi
       if ((!$OPT_DELETE)); then
            echo "Linking ${target##*/}/${source##*/}"
            ln -s "${source}" "${targetFile}"
        fi
    done;
done;

if ((!$OPT_DELETE)); then
    composer install
else
    rm -rf vendor composer.lock
    echo "Removed all symbolic links and composer files."
fi

echo
echo "Done!"
echo
