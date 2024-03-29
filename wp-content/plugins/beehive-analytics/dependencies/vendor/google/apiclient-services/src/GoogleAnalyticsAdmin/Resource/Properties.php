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
namespace Beehive\Google\Service\GoogleAnalyticsAdmin\Resource;

use Beehive\Google\Service\GoogleAnalyticsAdmin\GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionRequest;
use Beehive\Google\Service\GoogleAnalyticsAdmin\GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionResponse;
use Beehive\Google\Service\GoogleAnalyticsAdmin\GoogleAnalyticsAdminV1betaDataRetentionSettings;
use Beehive\Google\Service\GoogleAnalyticsAdmin\GoogleAnalyticsAdminV1betaListPropertiesResponse;
use Beehive\Google\Service\GoogleAnalyticsAdmin\GoogleAnalyticsAdminV1betaProperty;
/**
 * The "properties" collection of methods.
 * Typical usage is:
 *  <code>
 *   $analyticsadminService = new Google\Service\GoogleAnalyticsAdmin(...);
 *   $properties = $analyticsadminService->properties;
 *  </code>
 */
class Properties extends \Beehive\Google\Service\Resource
{
    /**
     * Acknowledges the terms of user data collection for the specified property.
     * This acknowledgement must be completed (either in the Google Analytics UI or
     * via this API) before MeasurementProtocolSecret resources may be created.
     * (properties.acknowledgeUserDataCollection)
     *
     * @param string $property Required. The property for which to acknowledge user
     * data collection.
     * @param GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionRequest $postBody
     * @param array $optParams Optional parameters.
     * @return GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionResponse
     */
    public function acknowledgeUserDataCollection($property, GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionRequest $postBody, $optParams = [])
    {
        $params = ['property' => $property, 'postBody' => $postBody];
        $params = \array_merge($params, $optParams);
        return $this->call('acknowledgeUserDataCollection', [$params], GoogleAnalyticsAdminV1betaAcknowledgeUserDataCollectionResponse::class);
    }
    /**
     * Creates an "GA4" property with the specified location and attributes.
     * (properties.create)
     *
     * @param GoogleAnalyticsAdminV1betaProperty $postBody
     * @param array $optParams Optional parameters.
     * @return GoogleAnalyticsAdminV1betaProperty
     */
    public function create(GoogleAnalyticsAdminV1betaProperty $postBody, $optParams = [])
    {
        $params = ['postBody' => $postBody];
        $params = \array_merge($params, $optParams);
        return $this->call('create', [$params], GoogleAnalyticsAdminV1betaProperty::class);
    }
    /**
     * Marks target Property as soft-deleted (ie: "trashed") and returns it. This
     * API does not have a method to restore soft-deleted properties. However, they
     * can be restored using the Trash Can UI. If the properties are not restored
     * before the expiration time, the Property and all child resources (eg:
     * GoogleAdsLinks, Streams, UserLinks) will be permanently purged.
     * https://support.google.com/analytics/answer/6154772 Returns an error if the
     * target is not found, or is not a GA4 Property. (properties.delete)
     *
     * @param string $name Required. The name of the Property to soft-delete.
     * Format: properties/{property_id} Example: "properties/1000"
     * @param array $optParams Optional parameters.
     * @return GoogleAnalyticsAdminV1betaProperty
     */
    public function delete($name, $optParams = [])
    {
        $params = ['name' => $name];
        $params = \array_merge($params, $optParams);
        return $this->call('delete', [$params], GoogleAnalyticsAdminV1betaProperty::class);
    }
    /**
     * Lookup for a single "GA4" Property. (properties.get)
     *
     * @param string $name Required. The name of the property to lookup. Format:
     * properties/{property_id} Example: "properties/1000"
     * @param array $optParams Optional parameters.
     * @return GoogleAnalyticsAdminV1betaProperty
     */
    public function get($name, $optParams = [])
    {
        $params = ['name' => $name];
        $params = \array_merge($params, $optParams);
        return $this->call('get', [$params], GoogleAnalyticsAdminV1betaProperty::class);
    }
    /**
     * Returns the singleton data retention settings for this property.
     * (properties.getDataRetentionSettings)
     *
     * @param string $name Required. The name of the settings to lookup. Format:
     * properties/{property}/dataRetentionSettings Example:
     * "properties/1000/dataRetentionSettings"
     * @param array $optParams Optional parameters.
     * @return GoogleAnalyticsAdminV1betaDataRetentionSettings
     */
    public function getDataRetentionSettings($name, $optParams = [])
    {
        $params = ['name' => $name];
        $params = \array_merge($params, $optParams);
        return $this->call('getDataRetentionSettings', [$params], GoogleAnalyticsAdminV1betaDataRetentionSettings::class);
    }
    /**
     * Returns child Properties under the specified parent Account. Only "GA4"
     * properties will be returned. Properties will be excluded if the caller does
     * not have access. Soft-deleted (ie: "trashed") properties are excluded by
     * default. Returns an empty list if no relevant properties are found.
     * (properties.listProperties)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string filter Required. An expression for filtering the results of
     * the request. Fields eligible for filtering are: `parent:`(The resource name
     * of the parent account/property) or `ancestor:`(The resource name of the
     * parent account) or `firebase_project:`(The id or number of the linked
     * firebase project). Some examples of filters: ``` | Filter | Description |
     * |-----------------------------|-------------------------------------------| |
     * parent:accounts/123 | The account with account id: 123. | |
     * parent:properties/123 | The property with property id: 123. | |
     * ancestor:accounts/123 | The account with account id: 123. | |
     * firebase_project:project-id | The firebase project with id: project-id. | |
     * firebase_project:123 | The firebase project with number: 123. | ```
     * @opt_param int pageSize The maximum number of resources to return. The
     * service may return fewer than this value, even if there are additional pages.
     * If unspecified, at most 50 resources will be returned. The maximum value is
     * 200; (higher values will be coerced to the maximum)
     * @opt_param string pageToken A page token, received from a previous
     * `ListProperties` call. Provide this to retrieve the subsequent page. When
     * paginating, all other parameters provided to `ListProperties` must match the
     * call that provided the page token.
     * @opt_param bool showDeleted Whether to include soft-deleted (ie: "trashed")
     * Properties in the results. Properties can be inspected to determine whether
     * they are deleted or not.
     * @return GoogleAnalyticsAdminV1betaListPropertiesResponse
     */
    public function listProperties($optParams = [])
    {
        $params = [];
        $params = \array_merge($params, $optParams);
        return $this->call('list', [$params], GoogleAnalyticsAdminV1betaListPropertiesResponse::class);
    }
    /**
     * Updates a property. (properties.patch)
     *
     * @param string $name Output only. Resource name of this property. Format:
     * properties/{property_id} Example: "properties/1000"
     * @param GoogleAnalyticsAdminV1betaProperty $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string updateMask Required. The list of fields to be updated.
     * Field names must be in snake case (e.g., "field_to_update"). Omitted fields
     * will not be updated. To replace the entire entity, use one path with the
     * string "*" to match all fields.
     * @return GoogleAnalyticsAdminV1betaProperty
     */
    public function patch($name, GoogleAnalyticsAdminV1betaProperty $postBody, $optParams = [])
    {
        $params = ['name' => $name, 'postBody' => $postBody];
        $params = \array_merge($params, $optParams);
        return $this->call('patch', [$params], GoogleAnalyticsAdminV1betaProperty::class);
    }
    /**
     * Updates the singleton data retention settings for this property.
     * (properties.updateDataRetentionSettings)
     *
     * @param string $name Output only. Resource name for this DataRetentionSetting
     * resource. Format: properties/{property}/dataRetentionSettings
     * @param GoogleAnalyticsAdminV1betaDataRetentionSettings $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string updateMask Required. The list of fields to be updated.
     * Field names must be in snake case (e.g., "field_to_update"). Omitted fields
     * will not be updated. To replace the entire entity, use one path with the
     * string "*" to match all fields.
     * @return GoogleAnalyticsAdminV1betaDataRetentionSettings
     */
    public function updateDataRetentionSettings($name, GoogleAnalyticsAdminV1betaDataRetentionSettings $postBody, $optParams = [])
    {
        $params = ['name' => $name, 'postBody' => $postBody];
        $params = \array_merge($params, $optParams);
        return $this->call('updateDataRetentionSettings', [$params], GoogleAnalyticsAdminV1betaDataRetentionSettings::class);
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(Properties::class, 'Beehive\\Google_Service_GoogleAnalyticsAdmin_Resource_Properties');
