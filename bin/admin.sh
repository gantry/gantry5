#!/bin/bash
GIT_SOURCE=${PWD}
GIT_TARGET=${GIT_SOURCE}/platforms
OPT_DELETE=0

echo
echo "Initialize Gantry Admin"
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
    "platforms/common/admin/assets"
)
targets=(
    "joomla/com_gantryadmin"
    "wordpress/plugins/gantryadmin"
    "grav/plugins/gantryadmin"
    "standalone/gantryadmin"
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
