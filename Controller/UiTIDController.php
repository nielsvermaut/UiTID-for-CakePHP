<?php
App::import('Vendor', 'OAuth/OAuthClient');
class UiTIDController extends UiTIDAppController{

//Fetches the application settings
private function createClient() {
        var_dump(Configure::read('UiTID.private'));
        return new OAuthClient(Configure::read('UiTID.public'), Configure::read('UiTID.private'));
    }

public function uitid() {

        if(isset($this->request->query['router'])){
            $this->Session->write('router', $this->request->query['router']);
        }

        $client = $this->createClient();
        $requestToken = $client->getRequestToken(Configure::read('UiTID.server') . '/requestToken', 'http://' . $_SERVER["HTTP_HOST"] . $this->base .'/users/callback');
        if (!empty($requestToken)) {
            $this->Session->write('uitid_request_token', $requestToken);
            $this->redirect(Configure::read('UiTID.server') . '/auth/authorize?oauth_token=' . $requestToken->key);
        } else {

        }
    }

    public function callback() {
        $requestToken = $this->Session->read('uitid_request_token');
        $client = $this->createClient();
        $accessToken = $client->getAccessToken(Configure::read('UiTID.server') .'/accessToken', $requestToken);
        if ($accessToken) {
            //Do something if the accestoken is succesfull (Check if a user exists)
			//You'll want to do this with
			/*
			*
			* $this->User->find('first', array('conditions' => array('User.uitid' => $accesToken->userId)));
			*
            */
        }
    }
	
	 private function xmlToArray($xml, $options = array()) {
        $defaults = array(
            'namespaceSeparator' => ':',//you may want this to be something other than a colon
            'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array(),   //array of xml tag names which should always become arrays
            'autoArray' => true,        //only create arrays for tags which appear more than once
            'textContent' => '$',       //key used for the text content of elements
            'autoText' => true,         //skip textContent key if node has no attributes or child nodes
            'keySearch' => false,       //optional search and replace on tag and attribute names
            'keyReplace' => false       //replace values for above search values (as passed to str_replace())
        );
        $options = array_merge($defaults, $options);
        $namespaces = $xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace

        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }

        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray = $this->xmlToArray($childXml, $options);
                list($childTagName, $childProperties) = each($childArray);

                //replace characters in tag name
                if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                //add namespace prefix, if any
                if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

                if (!isset($tagsArray[$childTagName])) {
                    //only entry with this key
                    //test if tags of this type should always be arrays, no matter the element count
                    $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                            ? array($childProperties) : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    //key already exists and is integer indexed array
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }
     }
}