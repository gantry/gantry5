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
        'platforms/joomla/plg_system_gantryadmin'
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
        'plugins/system/gantryadmin'
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
elif [ -f $GIT_TARGET/mage ]; then
    PLATFORM=Magento
    sources=(
        'platforms/magento/code/local/Gantry'
        'platforms/common'
        'src'
        'vendor'
        'platforms/magento/design/adminhtml/default/default/template/gantry'
        'platforms/magento/etc/modules/Gantry_Adminblock.xml'

        'themes/gantry/magento/design'
        'themes/gantry/common'
        'src'
        'vendor'
        'themes/gantry/magento/skin'
        'themes/gantry/common'
        )
    targets=(
        'app/code/local/Gantry'
        'app/code/local/Gantry/common'
        'app/code/local/Gantry/src'
        'app/code/local/Gantry/vendor'
        'app/design/adminhtml/default/default/template/gantry'
        'app/etc/modules/Gantry_Adminblock.xml'

        'app/design/frontend/gantry'
        'app/design/frontend/gantry/default/gantry/common'
        'app/design/frontend/gantry/default/gantry/src'
        'app/design/frontend/gantry/default/gantry/vendor'
        'skin/frontend/gantry'
        'skin/frontend/gantry/default/common'
        )
    mkdir "$GIT_TARGET/app/code/local"
elif [ -f $GIT_TARGET/viewtopic.php ]; then
    PLATFORM=phpBB
    sources=(
        'platforms/phpbb'
        'platforms/common'
        'src'
        'vendor'
        'themes/gantry/phpbb'
        'themes/gantry/common'
        'src'
        'vendor'
        )
    targets=(
        'ext/rockettheme'
        'ext/rockettheme/gantry/common'
        'ext/rockettheme/gantry/src'
        'ext/rockettheme/gantry/vendor'
        'styles/gantry'
        'styles/gantry/common'
        'styles/gantry/src'
        'styles/gantry/vendor'
        )
elif [ -f $GIT_TARGET/.prime ]; then
    PLATFORM=prime
    sources=(
        'platforms/prime/admin'
        'platforms/common'
        'platforms/prime/config'
        'platforms/prime/includes'
        'platforms/prime/layouts'
        'platforms/prime/media'
        'platforms/prime/pages'
        'platforms/prime/positions'
        'engines'
        'assets'
        'src'
        'vendor'
        'platforms/prime/.htaccess'
        'platforms/prime/index.php'
        'themes/gantry/prime'
        'themes/gantry/common'
        'themes/hydrogen/prime'
        'themes/hydrogen/common'
        )
    targets=(
        'admin'
        'admin/common'
        'config'
        'includes'
        'layouts'
        'media'
        'pages'
        'positions'
        'engines'
        'assets'
        'src'
        'vendor'
        '.htaccess'
        'index.php'
        'themes/gantry'
        'themes/gantry/common'
        'themes/hydrogen'
        'themes/hydrogen/common'
        )
    rm -rf "$GIT_TARGET/cache"
    mkdir "$GIT_TARGET/cache"
    mkdir "$GIT_TARGET/themes"
    chmod a+w "$GIT_TARGET/cache"

else
    echo
	echo "ERROR: No CMS installation was found!"
	echo "Please run this command in your web root directory!"
	echo "To install Gantry Prime, please run 'touch .prime' where you want to install it into."
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
