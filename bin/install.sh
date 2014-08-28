#!/bin/bash
GIT_SOURCE=${0%/*/*}
GIT_TARGET=$PWD
OPT_DELETE=0

if [ -f $GIT_TARGET/configuration.php ]; then
    PLATFORM=Joomla
    sources=('themes/nucleus/joomla')
    targets=('templates/nucleus')
elif [ -f $GIT_TARGET/wp-config.php ]; then
    PLATFORM=WordPress
    sources=('themes/nucleus/wordpress')
    targets=('wp-content/themes/nucleus')
elif [ -f $GIT_TARGET/system/config/system.yaml ]; then
    PLATFORM=Grav
    sources=('themes/nucleus/grav')
    targets=('user/themes/nucleus')
elif [ -f $GIT_TARGET/.standalone ]; then
    PLATFORM=Standalone
    sources=('themes/nucleus/standalone')
    targets=('nucleus')

else
    echo
	echo "ERROR: No Joomla / WordPress / Grav installation was found!"
	echo "Please run this command in your web root directory!"
	echo
	echo "Add symbolic links to repository (as user www-data):"
	echo "sudo -u www-data $0"
	echo "Remove symbolic links (as user www-data):"
	echo "sudo -u www-data $0 -d"
	echo
	exit 1;
fi

echo
echo "Link Gantry development tree into web site."
echo "GIT repository in: $GIT_SOURCE"
echo "$PLATFORM in: $GIT_TARGET"
echo

while getopts ":d" optname
	do
		case "$optname" in
			"d")
				OPT_DELETE=1
				;;
			"?")
				echo "Unknown option $OPTARG"
				exit 1
				;;

		esac
	done

for (( i = 0 ; i < ${#sources[@]} ; i++ ))
do
	source="$GIT_SOURCE/${sources[$i]}"
	target="$GIT_TARGET/${targets[$i]}"
	if [ ! -L $target ]; then
		rm -rf "$target"
	else
		unlink "$target"
	fi
	if ((!$OPT_DELETE)); then
		echo "Linking ${targets[$i]}"
		ln -s "$source" "$target"
	fi
done;

if (($OPT_DELETE)); then
	echo "Removed development version of Gantry from your web site."
fi

echo
echo "Done!"
echo
