/**
 * @copyright  Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

if (Joomla && Joomla.getOptions('js-extensions-update')) {
  const update = (type, text) => {
    const link = document.getElementById('plg_quickicon_gantry5');
    if (link) {
      link.classList.add(type);
    }
    link.querySelectorAll('span.j-links-link').forEach(span => {
      span.innerHTML = Joomla.sanitizeHtml(text);
    });
  };
  const fetchUpdate = () => {
    const options = Joomla.getOptions('js-gantry5-update');

    /**
     * DO NOT use fetch() for QuickIcon requests. They must be queued.
     *
     * @see https://github.com/joomla/joomla-cms/issues/38001
     */
    Joomla.enqueueRequest({
      url: options.ajaxUrl,
      method: 'GET',
      promise: true
    }).then(xhr => {
      const response = xhr.responseText;
      const updateInfoList = JSON.parse(response);
      if (Array.isArray(updateInfoList)) {
        if (updateInfoList.length === 0) {
          // No updates
          update('success', Joomla.Text._('PLG_QUICKICON_GANTRY5_UPTODATE'));
        } else {
          const updateInfo = updateInfoList.shift();
          if (updateInfo.version !== options.version) {
            update('danger', Joomla.Text._('PLG_QUICKICON_GANTRY5_UPDATEFOUND').replace('%s', `<span class="badge text-dark bg-light"> \u200E ${updateInfo.version}</span>`));
          } else {
            update('success', Joomla.Text._('PLG_QUICKICON_GANTRY5_UPTODATE'));
          }
        }
      } else {
        // An error occurred
        update('danger', Joomla.Text._('PLG_QUICKICON_GANTRY5_ERROR'));
      }
    }).catch(() => {
      // An error occurred
      update('danger', Joomla.Text._('PLG_QUICKICON_GANTRY5_ERROR'));
    });
  };

  // Give some times to the layout and other scripts to settle their stuff
  window.addEventListener('load', () => setTimeout(fetchUpdate, 300));
}
