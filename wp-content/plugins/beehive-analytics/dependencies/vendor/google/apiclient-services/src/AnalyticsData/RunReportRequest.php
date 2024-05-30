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
namespace Beehive\Google\Service\AnalyticsData;

class RunReportRequest extends \Beehive\Google\Collection
{
    protected $collection_key = 'orderBys';
    /**
     * @var CohortSpec
     */
    public $cohortSpec;
    protected $cohortSpecType = CohortSpec::class;
    protected $cohortSpecDataType = '';
    /**
     * @var string
     */
    public $currencyCode;
    /**
     * @var DateRange[]
     */
    public $dateRanges;
    protected $dateRangesType = DateRange::class;
    protected $dateRangesDataType = 'array';
    /**
     * @var FilterExpression
     */
    public $dimensionFilter;
    protected $dimensionFilterType = FilterExpression::class;
    protected $dimensionFilterDataType = '';
    /**
     * @var Dimension[]
     */
    public $dimensions;
    protected $dimensionsType = Dimension::class;
    protected $dimensionsDataType = 'array';
    /**
     * @var bool
     */
    public $keepEmptyRows;
    /**
     * @var string
     */
    public $limit;
    /**
     * @var string[]
     */
    public $metricAggregations;
    /**
     * @var FilterExpression
     */
    public $metricFilter;
    protected $metricFilterType = FilterExpression::class;
    protected $metricFilterDataType = '';
    /**
     * @var Metric[]
     */
    public $metrics;
    protected $metricsType = Metric::class;
    protected $metricsDataType = 'array';
    /**
     * @var string
     */
    public $offset;
    /**
     * @var OrderBy[]
     */
    public $orderBys;
    protected $orderBysType = OrderBy::class;
    protected $orderBysDataType = 'array';
    /**
     * @var string
     */
    public $property;
    /**
     * @var bool
     */
    public $returnPropertyQuota;
    /**
     * @param CohortSpec
     */
    public function setCohortSpec(CohortSpec $cohortSpec)
    {
        $this->cohortSpec = $cohortSpec;
    }
    /**
     * @return CohortSpec
     */
    public function getCohortSpec()
    {
        return $this->cohortSpec;
    }
    /**
     * @param string
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }
    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }
    /**
     * @param DateRange[]
     */
    public function setDateRanges($dateRanges)
    {
        $this->dateRanges = $dateRanges;
    }
    /**
     * @return DateRange[]
     */
    public function getDateRanges()
    {
        return $this->dateRanges;
    }
    /**
     * @param FilterExpression
     */
    public function setDimensionFilter(FilterExpression $dimensionFilter)
    {
        $this->dimensionFilter = $dimensionFilter;
    }
    /**
     * @return FilterExpression
     */
    public function getDimensionFilter()
    {
        return $this->dimensionFilter;
    }
    /**
     * @param Dimension[]
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }
    /**
     * @return Dimension[]
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }
    /**
     * @param bool
     */
    public function setKeepEmptyRows($keepEmptyRows)
    {
        $this->keepEmptyRows = $keepEmptyRows;
    }
    /**
     * @return bool
     */
    public function getKeepEmptyRows()
    {
        return $this->keepEmptyRows;
    }
    /**
     * @param string
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    /**
     * @return string
     */
    public function getLimit()
    {
        return $this->limit;
    }
    /**
     * @param string[]
     */
    public function setMetricAggregations($metricAggregations)
    {
        $this->metricAggregations = $metricAggregations;
    }
    /**
     * @return string[]
     */
    public function getMetricAggregations()
    {
        return $this->metricAggregations;
    }
    /**
     * @param FilterExpression
     */
    public function setMetricFilter(FilterExpression $metricFilter)
    {
        $this->metricFilter = $metricFilter;
    }
    /**
     * @return FilterExpression
     */
    public function getMetricFilter()
    {
        return $this->metricFilter;
    }
    /**
     * @param Metric[]
     */
    public function setMetrics($metrics)
    {
        $this->metrics = $metrics;
    }
    /**
     * @return Metric[]
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
    /**
     * @param string
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }
    /**
     * @return string
     */
    public function getOffset()
    {
        return $this->offset;
    }
    /**
     * @param OrderBy[]
     */
    public function setOrderBys($orderBys)
    {
        $this->orderBys = $orderBys;
    }
    /**
     * @return OrderBy[]
     */
    public function getOrderBys()
    {
        return $this->orderBys;
    }
    /**
     * @param string
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }
    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
    /**
     * @param bool
     */
    public function setReturnPropertyQuota($returnPropertyQuota)
    {
        $this->returnPropertyQuota = $returnPropertyQuota;
    }
    /**
     * @return bool
     */
    public function getReturnPropertyQuota()
    {
        return $this->returnPropertyQuota;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(RunReportRequest::class, 'Beehive\\Google_Service_AnalyticsData_RunReportRequest');
