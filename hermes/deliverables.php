<?php
/**
 * $Horde: hermes/deliverables.php,v 1.10.2.1 2009-01-06 15:23:58 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jason M. Felice <jason.m.felice@gmail.com>
 */

@define('HERMES_BASE', dirname(__FILE__));
require_once HERMES_BASE . '/lib/base.php';
require_once HERMES_BASE . '/lib/Forms/Deliverable.php';
require_once 'Horde/Variables.php';
require_once 'Horde/Tree.php';

$vars = Variables::getDefaultVariables();

switch ($vars->get('formname')) {
case 'deliverableform':
    $form = new DeliverableForm($vars);
    $form->validate($vars);
    if ($form->isValid()) {
        $form->getInfo($vars, $info);
        if (!empty($info['deliverable_id'])) {
            $info['id'] = $info['deliverable_id'];
            if (empty($info['parent'])) {
                $origdeliv = $hermes->getDeliverableByID($info['id']);
                if (!is_a($origdeliv, 'PEAR_Error')) {
                    $info['parent'] = $origdeliv['parent'];
                }
            }
        }
        $res = $hermes->updateDeliverable($info);
        if (is_a($res, 'PEAR_Error')) {
            $notification->push(sprintf(_("Error saving deliverable: %s"),
                                        $res->getMessage()),
                                'horde.error');
        } else {
            $notification->push(_("Deliverable saved successfully."),
                                'horde.success');
            $vars = new Variables(array('client_id' => $vars->get('client_id')));
        }
    }
    break;

case 'deletedeliverable':
    $res = $hermes->deleteDeliverable($vars->get('delete'));
    if (is_a($res, 'PEAR_Error')) {
        $notification->push(sprintf(_("Error deleting deliverable: %s"),
                                    $res->getMessage()), 'horde.error');
    } else {
        $notification->push(_("Deliverable successfully deleted."),
                            'horde.success');
    }
    break;
}

$title = _("Deliverables");
require HERMES_TEMPLATES . '/common-header.inc';
require HERMES_TEMPLATES . '/menu.inc';

$renderer = new Horde_Form_Renderer();

if (!$vars->exists('deliverable_id') && !$vars->exists('new')) {
    $clientSelector = new DeliverableClientSelector($vars);
    $clientSelector->renderActive($renderer, $vars, 'deliverables.php', 'post');
}

if ($vars->exists('deliverable_id') || $vars->exists('new')) {
    if ($vars->exists('deliverable_id')) {
        $deliverable = $hermes->getDeliverableByID($vars->get('deliverable_id'));
        if (is_a($deliverable, 'PEAR_Error')) {
            Horde::fatal($deliverable, __FILE__, __LINE__);
        }

        foreach ($deliverable as $name => $value) {
            $vars->set($name, $value);
        }
    }

    $form = new DeliverableForm($vars);
    $form->renderActive($renderer, $vars, 'deliverables.php', 'post');
} elseif ($vars->exists('client_id')) {
    $clients = Hermes::listClients();
    $clientname = $clients[$vars->get('client_id')];

    $deliverables = $hermes->listDeliverables(array('client_id' => $vars->get('client_id')));
    if (is_a($deliverables, 'PEAR_Error')) {
        Horde::fatal($deliverables, __FILE__, __LINE__);
    }

    $tree = Horde_Tree::factory('deliverables', 'javascript');
    $tree->setOption(array('class'       => 'item',
                           'alternate'   => true));

    foreach ($deliverables as $deliverable) {
        $params = array();
        $params['url'] = Horde::applicationUrl('deliverables.php');
        $params['url'] = Util::addParameter($params['url'], array('deliverable_id' => $deliverable['id'], 'client_id' => $vars->get('client_id')));
        $params['title'] = sprintf(_("Edit %s"), $deliverable['name']);

        $newdeliv = '&nbsp;' . Horde::link(Util::addParameter(Horde::applicationUrl('deliverables.php'), array('new' => 1, 'parent' => $deliverable['id'], 'client_id' => $vars->get('client_id'))), _("New Sub-deliverable")) . Horde::img('newdeliverable.png', _("New Sub-deliverable")) . '</a>';

        $deldeliv = '&nbsp;' . Horde::link(Util::addParameter(Horde::applicationUrl('deliverables.php'), array('formname' => 'deletedeliverable', 'delete' => $deliverable['id'], 'client_id' => $vars->get('client_id'))), _("Delete This Deliverable")) . Horde::img('delete.png', _("Delete This Deliverable"), '', $registry->getImageDir('horde')) . '</a>';

        /* Calculate the node's depth. */
        $depth = 0;
        $iterator = $deliverable;
        while (!empty($iterator['parent'])) {
            $depth++;
            $iterator = $deliverables[$iterator['parent']];
        }

        $tree->addNode($deliverable['id'], $deliverable['parent'],
                       $deliverable['name'], $depth, true, $params,
                       array($newdeliv, $deldeliv), array());
    }

    require HERMES_TEMPLATES . '/deliverables/list.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
