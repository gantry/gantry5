#!/bin/bash
GIT_SOURCE=${0%/*/*}
GIT_TARGET=$PWD
OPT_DELETE=0

if [ -f $GIT_TARGET/configuration.php ]; then
    PLATFORM=Joomla
    mkdir "$GIT_TARGET/libraries/gantry5"
    mkdir "$GIT_TARGET/media/gantry5"
    sources=(
        # Manifest files
        'platforms/joomla/pkg_gantry5.xml'
        'platforms/joomla/lib_gantry5/gantry5.xml'
        'platforms/joomla/gantry5_media/gantry5_media.xml'
        # Component
        'platforms/joomla/com_gantry5/site'
        'platforms/joomla/com_gantry5/admin'
        'platforms/common'
        # Library
        'src/classes'
        'platforms/joomla/lib_gantry5/language'
        'src/vendor'
        'src/Loader.php'
        # Plugins
        'platforms/joomla/plg_system_gantry5'
        'platforms/joomla/plg_quickicon_gantry5'
        # Media
        'assets'
        'engines'
        # Templates
        'themes/hydrogen/joomla'
        'themes/hydrogen/common'
    )
    targets=(
        # Manifest files
        'administrator/manifests/packages/pkg_gantry5.xml'
        'administrator/manifests/libraries/gantry5.xml'
        'administrator/manifests/files/gantry5_media.xml'
        # Component
        'components/com_gantry5'
        'administrator/components/com_gantry5'
        'administrator/components/com_gantry5/common'
        # Library
        'libraries/gantry5/classes'
        'libraries/gantry5/language'
        'libraries/gantry5/vendor'
        'libraries/gantry5/Loader.php'
        # Plugins
        'plugins/system/gantry5'
        'plugins/quickicon/gantry5'
        # Media
        'media/gantry5/assets'
        'media/gantry5/engines'
        # Templates
        'templates/g5_hydrogen'
        'templates/g5_hydrogen/common'
        )
elif [ -f $GIT_TARGET/wp-config.php ]; then
    PLATFORM=WordPress
    sources=(
        'platforms/wordpress/gantryadmin'
        'platforms/common'
        'src'
        'vendor'
        'themes/hydrogen/wordpress'
        'themes/hydrogen/common'
        'themes/hydrogen-demo/wordpress'
        'themes/hydrogen-demo/common'
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
        'wp-content/themes/hydrogen'
        'wp-content/themes/hydrogen/common'
        'wp-content/themes/hydrogen/src'
        'wp-content/themes/hydrogen/vendor'
        'wp-content/themes/hydrogen-demo'
        'wp-content/themes/hydrogen-demo/common'
        'wp-content/themes/hydrogen-demo/src'
        'wp-content/themes/hydrogen-demo/vendor'
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
        'user/themes/hydrogen'
        'user/themes/hydrogen/common'
        'user/themes/hydrogen/src'
        'user/themes/hydrogen/vendor'
        'user/themes/hydrogen-demo'
        'user/themes/hydrogen-demo/common'
        'user/themes/hydrogen-demo/src'
        'user/themes/hydrogen-demo/vendor'
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

        'themes/hydrogen/magento/design'
        'themes/hydrogen/common'
        'themes/hydrogen-demo/magento/design'
        'themes/hydrogen-demo/common'

        'src'
        'vendor'
        'themes/hydrogen/magento/skin'
        'themes/hydrogen/common'
        'themes/hydrogen-demo/magento/skin'
        'themes/hydrogen-demo/common'
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
        'themes/hydrogen/phpbb'
        'themes/hydrogen/common'
        'themes/hydrogen-demo/phpbb'
        'themes/hydrogen-demo/common'
        'src'
        'vendor'
        )
    targets=(
        'ext/rockettheme'
        'ext/rockettheme/gantry/common'
        'ext/rockettheme/gantry/src'
        'ext/rockettheme/gantry/vendor'
        'styles/hydrogen'
        'styles/hydrogen/common'
        'styles/hydrogen/src'
        'styles/hydrogen/vendor'
        'styles/hydrogen-demo'
        'styles/hydrogen-demo/common'
        'styles/hydrogen-demo/src'
        'styles/hydrogen-demo/vendor'
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
        'themes/hydrogen/prime'
        'themes/hydrogen/common'
        'themes/hydrogen-demo/prime'
        'themes/hydrogen-demo/common'
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
        'themes/hydrogen'
        'themes/hydrogen/common'
        'themes/hydrogen-demo'
        'themes/hydrogen-demo/common'
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
