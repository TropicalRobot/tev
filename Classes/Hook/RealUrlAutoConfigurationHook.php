<?php

namespace Tev\Tev\Hook;

/**
 * Used to modify the default RealURL autoconfig, and generate fixed post vars
 * for pages from the CMS config fields.
 *
 * @author Ben Constable <benconstable@3ev.com>, 3ev
 * @package Tev\Tev
 * @subpackage Hook
 */
class RealUrlAutoConfigurationHook
{
    /**
     * Overrides some of the built in options that RealURL autogenerates.
     *
     * @param array  $params
     * @param object $reference
     * @return array
     */
    public function updateConfig($params, $reference)
    {
        $config = $params['config'];

        // init
        $config['init']['emptyUrlReturnValue']    = '/';
        $config['init']['postVarSet_failureMode'] = 'ignore';
        $config['init']['enableCHashCache']       = true;

        // fileName
        $config['fileName']['acceptHTMLsuffix']   = 0;
        $config['fileName']['index']              = array(
            '.pdf' => array(
                'keyValues' => array(
                    'extension' => 'pdf'
                )
            )
        );

        // generate fixed post vars
        if (!isset($config['fixedPostVars']) || !is_array($config['fixedPostVars'])) {
            $config['fixedPostVars'] = array();
        }

        $pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'uid,pid,tx_tev_postvars',
            'pages',
            'deleted = 0'
        );

        foreach ($pages as $page) {
            // Check parent page for routing options if none found
            if (!($params = $page['tx_tev_postvars'])) {
                $parent = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    'uid,tx_tev_childpostvars',
                    'pages',
                    'deleted = 0 AND uid = ' . $page['pid']
                );

                if (count($parent)) {
                    $params = $parent[0]['tx_tev_childpostvars'];
                }
            }

            if ($params) {
                $params = explode('/', $params);

                if (!is_array($config['fixedPostVars'][$page['uid']])) {
                    $config['fixedPostVars'][$page['uid']] = array();
                }

                foreach ($params as $param) {
                    if (strlen($param)) {
                        $config['fixedPostVars'][$page['uid']][] = array(
                            'GETvar'  => $param
                        );
                    }
                }
            }
        }

        return $config;
    }
}
