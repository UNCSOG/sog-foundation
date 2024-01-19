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
namespace Beehive\Google\Service\Analytics\Resource;

use Beehive\Google\Service\Analytics\UserDeletionRequest;
/**
 * The "userDeletionRequest" collection of methods.
 * Typical usage is:
 *  <code>
 *   $analyticsService = new Google\Service\Analytics(...);
 *   $userDeletionRequest = $analyticsService->userDeletion_userDeletionRequest;
 *  </code>
 */
class UserDeletionUserDeletionRequest extends \Beehive\Google\Service\Resource
{
    /**
     * Insert or update a user deletion requests. (userDeletionRequest.upsert)
     *
     * @param UserDeletionRequest $postBody
     * @param array $optParams Optional parameters.
     * @return UserDeletionRequest
     */
    public function upsert(UserDeletionRequest $postBody, $optParams = [])
    {
        $params = ['postBody' => $postBody];
        $params = \array_merge($params, $optParams);
        return $this->call('upsert', [$params], UserDeletionRequest::class);
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(UserDeletionUserDeletionRequest::class, 'Beehive\\Google_Service_Analytics_Resource_UserDeletionUserDeletionRequest');
