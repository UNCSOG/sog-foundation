<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace Beehive\Google\Service\Analytics;

class EntityAdWordsLinkEntity extends \Beehive\Google\Model
{
    protected $webPropertyRefType = WebPropertyRef::class;
    protected $webPropertyRefDataType = '';
    public $webPropertyRef;
    /**
     * @param WebPropertyRef
     */
    public function setWebPropertyRef(WebPropertyRef $webPropertyRef)
    {
        $this->webPropertyRef = $webPropertyRef;
    }
    /**
     * @return WebPropertyRef
     */
    public function getWebPropertyRef()
    {
        return $this->webPropertyRef;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(EntityAdWordsLinkEntity::class, 'Beehive\\Google_Service_Analytics_EntityAdWordsLinkEntity');
