#!/bin/bash
GIT_SOURCE=${0%/*/*}
GIT_TARGET=$PWD
OPT_DELETE=0

if [ -f $GIT_TARGET/configuration.php ]; then
    PLATFORM=Joomla
    sources=(
        'platforms/joomla/com_gantryadmin'
        'platforms/common'
        'src'
        'vendor'
        'themes/gantry/joomla'
        'themes/gantry/common'
        'src'
        'vendor'
    )
    targets=(
        'administrator/components/com_gantryadmin'
        'administrator/components/com_gantryadmin/common'
        'administrator/components/com_gantryadmin/src'
        'administrator/components/com_gantryadmin/vendor'
        'templates/gantry'
        'templates/gantry/common'
        'templates/gantry/src'
        'templates/gantry/vendor'
        )
elif [ -f $GIT_TARGET/wp-config.php ]; then
    PLATFORM=WordPress
    sources=(
        'platforms/wordpress/gantryadmin'
        'platforms/common'
        'src'
        'vendor'
        'themes/gantry/wordpress'
        'themes/gantry/common'
        'src'
        'vendor'
        )
    targets=(
        'wp-content/plugins/gantryadmin'
        'wp-content/plugins/gantryadmin/common'
        'wp-content/plugins/gantryadmin/src'
        'wp-content/plugins/gantryadmin/vendor'
        'wp-content/themes/gantry'
        'wp-content/themes/gantry/common'
        'wp-content/themes/gantry/src'
        'wp-content/themes/gantry/vendor'
        )
elif [ -f $GIT_TARGET/system/config/system.yaml ]; then
    PLATFORM=Grav
    sources=(
        'platforms/grav/gantryadmin'
        'platforms/common'
        'src'
        'vendor'
        'themes/gantry/grav'
        'themes/gantry/common'
        'src'
        'vendor'
        )
    targets=(
        'user/plugins/gantryadmin'
        'user/plugins/gantryadmin/common'
        'user/plugins/gantryadmin/src'
        'user/plugins/gantryadmin/vendor'
        'user/themes/gantry'
        'user/themes/gantry/common'
        'user/themes/gantry/src'
        'user/themes/gantry/vendor'
        )
elif [ -f $GIT_TARGET/.standalone ]; then
    PLATFORM=Standalone
    sources=(
        'platforms/standalone/gantryadmin'
        'platforms/common'
        'src'
        'vendor'
        'themes/gantry/standalone'
        'themes/gantry/common'
        'src'
        'vendor'
        )
    targets=(
        'gantry/admin'
        'gantry/admin/common'
        'gantry/admin/src'
        'gantry/admin/vendor'
        'gantry'
        'gantry/common'
        'gantry/src'
        'gantry/vendor'
        )

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

old='xXx';
for (( i = 0 ; i < ${#sources[@]} ; i++ ))
do
	source="$GIT_SOURCE/${sources[$i]}"
	target="$GIT_TARGET/${targets[$i]}"
	if (($OPT_DELETE)); then
		if [[ $target == $old* ]]; then
			continue
		fi
	fi
	if [ ! -L $target ]; then
		rm -rf "$target"
	else
		unlink "$target"
	fi
	if ((!$OPT_DELETE)); then
		echo "Linking ${targets[$i]}"
		ln -s "$source" "$target"
	fi
	old=$target
done;

if (($OPT_DELETE)); then
	echo "Removed development version of Gantry from your web site."
fi

echo
echo "Done!"
echo
