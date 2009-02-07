<?php
/**
 * Jonah storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'database'      The name of the database.
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/db/jonah_news.sql
 * script. The needed tables are jonah_channels and jonah_stories.
 *
 * $Horde: jonah/lib/News/sql.php,v 1.108.2.1 2009/01/04 21:06:22 mrubinsk Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Jonah
 */
class Jonah_News_sql extends Jonah_News {

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Saves a channel to the backend.
     *
     * @param array $info  The channel to add.
     *                     Must contain a combination of the following
     *                     entries:
     * <pre>
     * 'channel_id'       If empty a new channel is being added, otherwise one
     *                    is being edited.
     * 'channel_name'     The headline.
     * 'channel_desc'     A description of this channel.
     * 'channel_type'     Whether internal or external.
     * 'channel_interval' If external then interval at which to refresh.
     * 'channel_link'     The link to the source.
     * 'channel_url'      The url from where to fetch the story list.
     * 'channel_image'    A channel image.
     * </pre>
     *
     * @return int|PEAR_Error  The channel ID on success, PEAR_Error on
     *                         failure.
     */
    function saveChannel(&$info)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        if (empty($info['channel_id'])) {
            $info['channel_id'] = $this->_db->nextId('jonah_channels');
            if (is_a($info['channel_id'], 'PEAR_Error')) {
                Horde::logMessage($info['channel_id'], __FILE__, __LINE__, PEAR_LOG_ERR);
                return $info['channel_id'];
            }
            $sql = 'INSERT INTO jonah_channels' .
                   ' (channel_id, channel_name, channel_type, channel_desc, channel_interval, channel_url, channel_link, channel_page_link, channel_story_url, channel_img)' .
                   ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $values = array();
        } else {
            $sql = 'UPDATE jonah_channels' .
                   ' SET channel_id = ?, channel_name = ?, channel_type = ?, channel_desc = ?, channel_interval = ?, channel_url = ?, channel_link = ?, channel_page_link = ?, channel_story_url = ?, channel_img = ?' .
                   ' WHERE channel_id = ?';
            $values = array((int)$info['channel_id']);
        }

        array_unshift($values,
                      (int)$info['channel_id'],
                      String::convertCharset($info['channel_name'], NLS::getCharset(), $this->_params['charset']),
                      (int)$info['channel_type'],
                      isset($info['channel_desc']) ? $info['channel_desc'] : null,
                      isset($info['channel_interval']) ? (int)$info['channel_interval'] : null,
                      isset($info['channel_url']) ? $info['channel_url'] : null,
                      isset($info['channel_link']) ? $info['channel_link'] : null,
                      isset($info['channel_page_link']) ? $info['channel_page_link'] : null,
                      isset($info['channel_story_url']) ? $info['channel_story_url'] : null,
                      isset($info['channel_img']) ? $info['channel_img'] : null);
        Horde::logMessage('SQL Query by Jonah_News_sql::saveChannel(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        return $info['channel_id'];
    }

    /**
     * Get a list of stored channels.
     *
     * @param integer $type  The type of channel to filter for. Possible
     *                       values are either JONAH_INTERNAL_CHANNEL
     *                       to fetch only a list of internal channels,
     *                       or JONAH_EXTERNAL_CHANNEL for only external.
     *                       If null both channel types are returned.
     *
     * @return mixed         An array of channels or PEAR_Error on error.
     */
    function getChannels($type = null)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $wsql = '';
        if (!is_null($type)) {
            if (!is_array($type)) {
                $type = array($type);
            }
            for ($i = 0, $i_max = count($type); $i < $i_max; ++$i) {
                $type[$i] = 'channel_type = ' . (int)$type[$i];
            }
            $wsql = 'WHERE ' . implode(' OR ', $type);
        }

        $sql = sprintf('SELECT channel_id, channel_name, channel_type, channel_updated FROM jonah_channels %s ORDER BY channel_name', $wsql);

        Horde::logMessage('SQL Query by Jonah_News_sql::getChannels(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getAll($sql, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['channel_name'] = String::convertCharset($result[$i]['channel_name'], $this->_params['charset']);
        }

        return $result;
    }

    /**
     */
    function _getChannel($channel_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT * FROM jonah_channels WHERE channel_id = ' . (int)$channel_id;

        Horde::logMessage('SQL Query by Jonah_News_sql::_getChannel(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getRow($sql, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif (empty($result)) {
            return PEAR::raiseError(sprintf(_("Channel id \"%s\" not found."), $channel_id));
        }

        $result['channel_name'] = String::convertCharset($result['channel_name'], $this->_params['charset']);
        if ($result['channel_type'] == JONAH_COMPOSITE_CHANNEL) {
            $channels = explode(':', $result['channel_url']);
            if (count($channels)) {
                $sql = 'SELECT MAX(channel_updated) FROM jonah_channels WHERE channel_id IN (' . implode(',', $channels) . ')';
                Horde::logMessage('SQL Query by Jonah_News_sql::_getChannel(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $updated = $this->_db->getOne($sql);
                if (is_a($updated, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                } else {
                    $result['channel_updated'] = $updated;
                    $this->_timestampChannel($channel_id, $updated);
                }
            }
        }

        return $result;
    }

    /**
     */
    function _timestampChannel($channel_id, $timestamp)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = sprintf('UPDATE jonah_channels SET channel_updated = %s WHERE channel_id = %s',
                       (int)$timestamp,
                       (int)$channel_id);
        Horde::logMessage('SQL Query by Jonah_News_sql::_timestampChannel(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
        return $result;
    }

    /**
     */
    function _readStory($story_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'UPDATE jonah_stories SET story_read = story_read + 1 WHERE story_id = ' . (int)$story_id;
        Horde::logMessage('SQL Query by Jonah_News_sql::_readStory(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
        return $result;
    }

    /**
     */
    function _deleteChannel($channel_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'DELETE FROM jonah_channels WHERE channel_id = ?';
        $values = array($channel_id);

        Horde::logMessage('SQL Query by Jonah_News_sql::deleteChannel(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        return $result;
    }

    /**
     * @param array &$info
     */
    function _saveStory(&$info)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        if (empty($info['story_id'])) {
            $info['story_id'] = $this->_db->nextId('jonah_stories');
            if (is_a($info['story_id'], 'PEAR_Error')) {
                Horde::logMessage($info['story_id'], __FILE__, __LINE__, PEAR_LOG_ERR);
                return $info['story_id'];
            }
            $channel = $this->getChannel($info['channel_id']);
            $permalink = $this->getStoryLink($channel, $info);
            $sql = 'INSERT INTO jonah_stories (story_id, channel_id, story_title, story_desc, story_body_type, story_body, story_url, story_published, story_updated, story_read, story_permalink) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $values = array($permalink);
        } else {
            $sql = 'UPDATE jonah_stories SET story_id = ?, channel_id = ?, story_title = ?, story_desc = ?, story_body_type = ?, story_body = ?, story_url = ?, story_published = ?, story_updated = ?, story_read = ? WHERE story_id = ?';
            $values = array((int)$info['story_id']);
        }

        if (empty($info['story_read'])) {
            $info['story_read'] = 0;
        }

        /* Deal with any tags */
        if (!empty($info['story_tags'])) {
            $tags = explode(',', $info['story_tags']);
        } else {
            $tags = array();
        }
        $this->writeTags($info['story_id'], $info['channel_id'], $tags);

        array_unshift($values,
                      (int)$info['story_id'],
                      (int)$info['channel_id'],
                      String::convertCharset($info['story_title'], NLS::getCharset(), $this->_params['charset']),
                      String::convertCharset($info['story_desc'], NLS::getCharset(), $this->_params['charset']),
                      $info['story_body_type'],
                      isset($info['story_body']) ? String::convertCharset($info['story_body'], NLS::getCharset(), $this->_params['charset']) : null,
                      isset($info['story_url']) ? $info['story_url'] : null,
                      isset($info['story_published']) ? (int)$info['story_published'] : null,
                      time(),
                      (int)$info['story_read']);

        Horde::logMessage('SQL Query by Jonah_News_sql::_saveStory(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        $this->_timestampChannel($info['channel_id'], time());
        return true;
    }

    /**
     * Converts the text fields of a story from the backend charset to the
     * output charset.
     *
     * @param array $story  A story hash.
     *
     * @return array  The converted hash.
     */
    function _convertFromBackend($story)
    {
        $story['story_title'] = String::convertCharset($story['story_title'], $this->_params['charset'], NLS::getCharset());
        $story['story_desc'] = String::convertCharset($story['story_desc'], $this->_params['charset'], NLS::getCharset());
        if (isset($story['story_body'])) {
            $story['story_body'] = String::convertCharset($story['story_body'], $this->_params['charset'], NLS::getCharset());
        }
        if (isset($story['story_tags'])) {
            $story['story_tags'] = String::convertCharset($story['story_tags'], $this->_params['charset'], NLS::getCharset());
        }
        return $story;
    }

    /**
     * Returns the most recent or all stories from a channel.
     *
     * @param integer $channel_id  The news channel to get stories from.
     * @param integer $max         The maximum number of stories to get.
     * @param integer $from        The number of the story to start with.
     * @param integer $date        The timestamp of the date to start with.
     * @param boolean $unreleased  Whether to return not yet released stories.
     * @param integer $order       How to order the results for internal
     *                             channels. Possible values are the
     *                             JONAH_ORDER_* constants.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     */
    function _getStories($channel_id, $max, $from = 0, $date = null,
                         $unreleased = false, $order = JONAH_ORDER_PUBLISHED)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT * FROM jonah_stories WHERE channel_id = ?';
        $values = array((int)$channel_id);

        if ($unreleased) {
            if ($date !== null) {
                $sql .= ' AND story_published <= ?';
                $values[] = $date;
            }
        } else {
            if ($date === null) {
                $date = time();
            } else {
                $date = max($date, time());
            }
            $sql .= ' AND story_published <= ?';
            $values[] = $date;
        }

        switch ($order) {
        case JONAH_ORDER_PUBLISHED:
            $sql .= ' ORDER BY story_published DESC';
            break;
        case JONAH_ORDER_READ:
            $sql .= ' ORDER BY story_read DESC';
            break;
        case JONAH_ORDER_COMMENTS:
            //@TODO
            break;
        }

        if (!is_null($max)) {
            $sql = $this->_db->modifyLimitQuery($sql, (int)$from, (int)$max, $values);
        }

        Horde::logMessage('SQL Query by Jonah_News_sql::_getStories(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        for ($i = 0; $i < count($result); $i++) {
            $result[$i] = $this->_convertFromBackend($result[$i]);
            if (empty($result[$i]['story_permalink'])) {
                $this->_addPermalink($result[$i]);
            }
            $tags = $this->readTags($result[$i]['story_id']);
            if (is_a($tags, 'PEAR_Error')) {
                return $tags;
            }
            $result[$i]['story_tags'] = $tags;
        }
        return $result;
    }

    /**
     */
    function _getStory($story_id, $read = false)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT * FROM jonah_stories WHERE story_id = ?';
        $values = array((int)$story_id);

        Horde::logMessage('SQL Query by Jonah_News_sql::_getStory(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif (empty($result)) {
            return PEAR::raiseError(sprintf(_("Story id \"%s\" not found."), $story_id));
        }
        $result['story_tags'] = $this->readTags($story_id);
        $result = $this->_convertFromBackend($result);
        if (empty($result['story_permalink'])) {
            $this->_addPermalink($result);
        }
        if ($read) {
            $this->_readStory($story_id);
        }

        return $result;
    }

     /**
     * Returns the total number of stories in the specified
     * channel
     *
     * @param int $channel_id  The Channel ID
     *
     * @return mixed  The count || PEAR_Error
     */
    function getStoryCount($channel_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT count(*) FROM jonah_stories WHERE channel_id = ?';
        $result = $this->_db->getOne($sql, $channel_id);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return (int)$result;
    }

    /**
     */
    function _getStoryByUrl($channel_id, $story_url)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT * FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_url = ?';
        $values = array((int)$channel_id, $story_url);

        Horde::logMessage('SQL Query by Jonah_News_sql::_getStoryByUrl(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif (empty($result)) {
            return PEAR::raiseError(sprintf(_("Story URL \"%s\" not found."), $story_url));
        }
        $result = $this->_convertFromBackend($result);
        if (empty($result['story_permalink'])) {
            $this->_addPermalink($result);
        }

        return $result;
    }

    /**
     * Adds a missing permalink to a story.
     *
     * @param array $story  A story hash.
     */
    function _addPermalink(&$story)
    {
        $channel = $this->getChannel($story['channel_id']);
        if (is_a($channel, 'PEAR_Error')) {
            return;
        }
        $sql = 'UPDATE jonah_stories SET story_permalink = ? WHERE story_id = ?';
        $values = array($this->getStoryLink($channel, $story), $story['story_id']);
        Horde::logMessage('SQL Query by Jonah_News_sql::_addPermalink(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (!is_a($result, 'PEAR_Error')) {
            $story['story_permalink'] = $values[0];
        }
    }

    /**
     * Gets the latest released story from a given internal channel
     *
     * @param int $channel_id  The channel id.
     *
     * @return int  The story id.
     */
    function getLatestStoryId($channel_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'SELECT story_id FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_published <= ?' .
               ' ORDER BY story_updated DESC';
        $values = array((int)$channel_id, time());

        Horde::logMessage('SQL Query by Jonah_News_sql::getLatestStoryId(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif (empty($result)) {
            return PEAR::raiseError(sprintf(_("Channel \"%s\" not found."), $channel_id));
        }

        return $result['story_id'];
    }

    /**
     */
    function deleteStory($channel_id, $story_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        $sql = 'DELETE FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_id = ?';
        $values = array((int)$channel_id, (int)$story_id);

        Horde::logMessage('SQL Query by Jonah_News_sql::deleteStory(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        $sql = 'DELETE FROM jonah_stories_tags ' .
               'WHERE channel_id = ? AND story_id = ?';
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        return true;
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean    True on success; PEAR_Error on failure.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        Horde::assertDriverConfig($this->_params, 'news',
            array('phptype', 'charset'),
            'jonah news SQL');

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_db = &DB::connect($this->_params,
                                  array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_db, 'PEAR_Error')) {
            return $this->_db;
        }

        // Set DB portability options.
        switch ($this->_db->phptype) {
        case 'mssql':
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        $this->_connected = true;
        return true;
    }


    /**
     * Write out the tags for a specific resource.
     *
     * @param int    $resource_id    The story we are tagging.
     * @param int    $channel_id     The channel id for the story we are tagging
     * @param array  $tags           An array of tags.
     *
     * @return mixed True | PEAR_Error
     */
    function writeTags($resource_id, $channel_id, $tags)
    {
        global $conf;

        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }
        // First, make sure all tag names exist in the DB.
        $tagkeys = array();
        $insert = $this->_db->prepare('INSERT INTO jonah_tags (tag_id, tag_name) VALUES(?, ?)');
        $query = $this->_db->prepare('SELECT tag_id FROM jonah_tags WHERE tag_name = ?');
        foreach ($tags as $tag) {
            $tag = String::lower(trim($tag));
            $results = $this->_db->execute($query, $this->_db->escapeSimple($tag));
            if (is_a($results, 'PEAR_Error')) {
                return $results;
            } elseif ($results->numRows() == 0) {
                $id = $this->_db->nextId('jonah_tags');
                $result = $this->_db->execute($insert, array($id, $tag));
                $tagkeys[] = $id;
            } else {
                $row = $results->fetchRow(DB_FETCHMODE_ASSOC);
                $tagkeys[] = $row['tag_id'];
            }
        }

        // Free our resources.
        $this->_db->freePrepared($insert, true);
        $this->_db->freePrepared($query, true);

        $sql = 'DELETE FROM jonah_stories_tags WHERE story_id = ' . (int)$resource_id;
        $query = $this->_db->prepare('INSERT INTO jonah_stories_tags (story_id, channel_id, tag_id) VALUES(?, ?, ?)');

        Horde::logMessage('SQL query by Jonah_News_sql::writeTags: ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $this->_db->query($sql);
        foreach ($tagkeys as $key) {
            $this->_db->execute($query, array($resource_id, $channel_id, $key));
        }
        $this->_db->freePrepared($query, true);

        /* @TODO We should clear at least any of our cached counts */
        return true;
    }

    /**
     * Retrieve the tags for a specified resource.
     *
     * @param int     $resource_id    The resource to get tags for.
     *
     * @return mixed  An array of tags | PEAR_Error
     */
    function readTags($resource_id)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }
        $sql = 'SELECT jonah_tags.tag_id, tag_name FROM jonah_tags INNER JOIN jonah_stories_tags ON jonah_stories_tags.tag_id = jonah_tags.tag_id WHERE jonah_stories_tags.story_id = ?';

        Horde::logMessage('SQL query by Jonah_News_sql::readTags ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

       $tags = $this->_db->getAssoc($sql, false, array($resource_id), false);
       return $tags;
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @param array $channel_id  An optional array of channel_ids.
     *
     * @return mixed  An array containing tag_name, and total | PEAR_Error
     */
    function listTagInfo($tags = array(), $channel_id = null)
    {
        require_once 'Horde/Cache.php';

        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }

        if (!is_array($channel_id) && is_numeric($channel_id)) {
            $channel_id = array($channel_id);
        }
        $cache = &Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'], Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));
        $cache_key = 'jonah_tags_' . md5(serialize($tags) . md5(serialize($channel_id)));
        $cache_value = $cache->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($cache_value) {
            return unserialize($cache_value);
        }

        $haveWhere = false;
        $sql = 'SELECT tn.tag_id, tag_name, COUNT(tag_name) total FROM jonah_tags as tn INNER JOIN jonah_stories_tags as t ON t.tag_id = tn.tag_id';
        if (count($tags)) {
            $sql .= ' WHERE tn.tag_id IN (' . implode(',', $tags) . ')';
            $haveWhere = true;
        }
        if (!is_null($channel_id)) {
            if (!$haveWhere) {
                $sql .= ' WHERE';
            } else {
                $sql .= ' AND';
            }
            $channels = array();
            foreach ($channel_id as $cid) {
                $c = $this->_getChannel($cid);
                if ($c['channel_type'] == JONAH_COMPOSITE_CHANNEL) {
                    $channels = array_merge($channels, explode(':', $c['channel_url']));
                }
            }
            $channel_id = array_merge($channel_id, $channels);
            $sql .= ' t.channel_id IN (' . implode(', ', $channel_id) . ')';
        }
        $sql .= ' GROUP BY tn.tag_id, tag_name ORDER BY total DESC;';
        $results = $this->_db->getAssoc($sql,true, array(), DB_FETCHMODE_ASSOC, false);
        $cache->set($cache_key, serialize($results));
        return $results;
    }

    /**
     * Search for resources matching the specified criteria
     *
     * @param array  $ids          An array of tag_ids to search for. Note that
     *                             these are AND'd together.
     * @param integer $max         The maximum number of stories to get. If
     *                             null, all stories will be returned.
     * @param integer $from        The number of the story to start with.
     * @param array $channel       Limit the result set to resources
     *                             present in these channels
     * @param integer $order       How to order the results for internal
     *                             channels. Possible values are the
     *                             JONAH_ORDER_* constants.
     *
     * @return mixed  Array of stories| PEAR_Error
     */
    function searchTagsById($ids, $max = 10, $from = 0, $channel_id = array(),
                            $order = JONAH_ORDER_PUBLISHED)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }
        if (!is_array($ids) || !count($ids)) {
            $stories[] = array();
        } else {
            $stories = array();
            $sql = 'SELECT DISTINCT s.story_id, s.channel_id FROM jonah_stories'
                   . ' as s, jonah_stories_tags as t';
            for ($i = 0; $i < count($ids); $i++) {
                $sql .= ', jonah_stories_tags as t' . $i;
            }
            $sql .= ' WHERE s.story_id = t.story_id';
            for ($i = 0 ; $i < count($ids); $i++) {
                $sql .= ' AND t' . $i . '.tag_id = ' . $ids[$i] . ' AND t'
                        . $i . '.story_id = t.story_id';
            }

            /* Limit to particular channels if requested */
            if (count($channel_id) > 0) {
                // Have to find out if we are a composite channel or not.
                $channels = array();
                foreach ($channel_id as $cid) {
                    $c = $this->_getChannel($cid);
                    if ($c['channel_type'] == JONAH_COMPOSITE_CHANNEL) {
                        $temp = explode(':', $c['channel_url']);
                        // Save a map of channels that are from composites.
                        foreach ($temp as $t) {
                            $cchannels[$t] = $cid;
                        }
                        $channels = array_merge($channels, $temp);
                    }
                }
                $channels = array_merge($channel_id, $channels);
                $timestamp = time();
                $sql .= ' AND t.channel_id IN (' . implode(', ', $channels)
                        . ') AND s.story_published IS NOT NULL AND '
                        . 's.story_published < ' . $timestamp;
            }

            switch ($order) {
            case JONAH_ORDER_PUBLISHED:
                $sql .= ' ORDER BY story_published DESC';
                break;
            case JONAH_ORDER_READ:
                $sql .= ' ORDER BY story_read DESC';
                break;
            case JONAH_ORDER_COMMENTS:
                //@TODO
                break;
            }

            /* Instantiate the channel object outside the loop if we
             * are only limiting to one channel. */
            if (count($channel_id) == 1) {
                $channel = $this->getChannel($channel_id[0]);
            }
            Horde::logMessage('SQL query by Jonah_News_sql::searchTags: ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $results = $this->_db->limitQuery($sql, $from, $max);
            if (is_a($results, 'PEAR_Error')) {
                return $results;
            }

            for ($i = 0; $i < $results->numRows(); $i++) {
                $row = $results->fetchRow();
                $story = $this->_getStory($row[0], false);
                if (count($channel_id > 1)) {
                    // Make sure we get the correct channel info for composites
                    if (!empty($cchannels[$story['channel_id']])) {
                        $channel = $this->getChannel($cchannels[$story['channel_id']]);
                    } else {
                        $channel = $this->getChannel($story['channel_id']);
                    }
                }

                /* Format story link. */
                $story['story_link'] = $this->getStoryLink($channel, $story);
                $story = array_merge($story, $channel);
                /* Format dates. */
                $date_format = $GLOBALS['prefs']->getValue('date_format');
                $story['story_updated_date'] = strftime($date_format, $story['story_updated']);
                if (!empty($story['story_published'])) {
                    $story['story_published_date'] = strftime($date_format, $story['story_published']);
                }

                $stories[] = $story;
            }
        }

        return $stories;
    }

    /**
     * Search for articles matching specific tag name(s).
     *
     * @see Jonah_News_sql::searchTagsById()
     */
    function searchTags($names, $max = 10, $from = 0, $channel_id = array(),
                        $order = JONAH_ORDER_PUBLISHED)
    {
        $ids = $this->getTagIds($names);
        if (is_a($ids, 'PEAR_Error')) {
            return $ids;
        }
        return $this->searchTagsById(array_values($ids), $max, $from, $channel_id, $order);
    }


    /**
     * Return a set of tag names given the tag_ids.
     *
     * @param array $ids  An array of tag_ids to get names for.
     *
     * @return mixed  An array of tag names | PEAR_Error.
     */
    function getTagNames($ids)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }
        $sql = 'SELECT t.tag_name FROM jonah_tags as t WHERE t.tag_id IN(';
        $needComma = false;
        foreach ($ids as $id) {
            $sql .= ($needComma ? ',' : '') . '\'' . $id . '\'';
            $needComma = true;
        }
        $sql .= ')';
        $tags = $this->_db->getCol($sql);
        return $tags;
    }

    /**
     * Return a set of tag_ids, given the tag name
     *
     * @param array $names  An array of names to search for
     *
     * @return mixed  An array of tag_name => tag_ids | PEAR_Error
     */
    function getTagIds($names)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            return $result;
        }
        $sql = 'SELECT t.tag_name, t.tag_id FROM jonah_tags as t WHERE t.tag_name IN(';
        $needComma = false;
        foreach ($names as $name) {
            $sql .= ($needComma ? ',' : '') . '\'' . $name . '\'';
            $needComma = true;
        }
        $sql .= ')';
        $tags = $this->_db->getAssoc($sql);
        return $tags;
    }

}
