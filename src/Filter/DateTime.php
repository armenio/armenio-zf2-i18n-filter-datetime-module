<?php
/**
 * Rafael Armenio <rafael.armenio@gmail.com>
 *
 * @link http://github.com/armenio for the source repository
 */
 
namespace Armenio\I18n\Filter;

use Locale;
use IntlDateFormatter;
use Traversable;
use IntlException;
use Zend\I18n\Exception as I18nException;
use Zend\Filter\AbstractFilter;
use Zend\Filter\Exception as FilterException;

class DateTime extends AbstractFilter
{
	/**
	 * Optional locale
	 *
	 * @var string|null
	 */
	protected $locale;

	/**
	 * @var int
	 */
	protected $dateType;

	/**
	 * @var int
	 */
	protected $timeType;

	/**
	 * Optional timezone
	 *
	 * @var string
	 */
	protected $timezone;

	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var int
	 */
	protected $calendar;

	/**
	 * IntlDateFormatter instances
	 *
	 * @var array
	 */
	protected $formatters = [];

	/**
	 * Sets filter options
	 *
	 * @param array|\Traversable $options
	 */
	public function __construct($options = null)
	{
		if ($options) {
			$this->setOptions($options);
		}
	}

	/**
	 * Sets the calendar to be used by the IntlDateFormatter
	 *
	 * @param int|null $calendar
	 * @return DateTime provides fluent interface
	 */
	public function setCalendar($calendar)
	{
		$this->calendar = $calendar;

		return $this;
	}

	/**
	 * Returns the calendar to by the IntlDateFormatter
	 *
	 * @return int
	 */
	public function getCalendar()
	{
		return $this->calendar ?: IntlDateFormatter::GREGORIAN;
	}

	/**
	 * Sets the date format to be used by the IntlDateFormatter
	 *
	 * @param int|null $dateType
	 * @return DateTime provides fluent interface
	 */
	public function setDateType($dateType)
	{
		$this->dateType = $dateType;

		return $this;
	}

	/**
	 * Returns the date format used by the IntlDateFormatter
	 *
	 * @return int
	 */
	public function getDateType()
	{
		return $this->dateType;
	}

	/**
	 * Sets the pattern to be used by the IntlDateFormatter
	 *
	 * @param string|null $pattern
	 * @return DateTime provides fluent interface
	 */
	public function setPattern($pattern)
	{
		$this->pattern = $pattern;

		return $this;
	}

	/**
	 * Returns the pattern used by the IntlDateFormatter
	 *
	 * @return string
	 */
	public function getPattern()
	{
		return $this->pattern;
	}

	/**
	 * Sets the time format to be used by the IntlDateFormatter
	 *
	 * @param int|null $timeType
	 * @return DateTime provides fluent interface
	 */
	public function setTimeType($timeType)
	{
		$this->timeType = $timeType;

		return $this;
	}

	/**
	 * Returns the time format used by the IntlDateFormatter
	 *
	 * @return int
	 */
	public function getTimeType()
	{
		return $this->timeType;
	}

	/**
	 * Set locale to use instead of the default
	 *
	 * @param  string $locale
	 * @return DateTime provides fluent interface
	 */
	public function setLocale($locale)
	{
		$this->locale = (string) $locale;
		return $this;
	}

	/**
	 * Get the locale to use
	 *
	 * @return string|null
	 */
	public function getLocale()
	{
		return $this->locale ?: Locale::getDefault();
	}

	/**
	 * Set timezone to use instead of the default
	 *
	 * @param  string $timezone
	 * @return DateTime provides fluent interface
	 */
	public function setTimezone($timezone)
	{
		$this->timezone = (string) $timezone;

		return $this;
	}

	/**
	 * Get the timezone to use
	 *
	 * @return string|null
	 */
	public function getTimezone()
	{
		return $this->timezone ?: date_default_timezone_get();
	}

	/**
	 * Returns a non lenient configured IntlDateFormatter
	 *
	 * @return IntlDateFormatter
	 */
	public function filter($value)
	{
		if (!is_string($value)) {
			return $value;
		}

		$dateType = $this->getDateType();
		$timeType = $this->getDateType();
		$locale = $this->getLocale();
		$timezone = $this->getTimezone();
		$calendar = $this->getCalendar();
		$pattern = $this->getPattern();

		$formatterId = md5($dateType . "\0" . $timeType . "\0" . $locale . "\0" . $timezone . "\0" . $calendar ."\0" . $pattern);

		if (!isset($this->formatters[$formatterId])) {
			try {
				$this->formatters[$formatterId] = new IntlDateFormatter(
					$locale,
					$dateType,
					$timeType,
					$timezone,
					IntlDateFormatter::GREGORIAN
				);

				$this->formatters[$formatterId]->setLenient(false);

				$this->setTimezone($this->formatters[$formatterId]->getTimezone()->getID());
				$this->setCalendar($this->formatters[$formatterId]->getCalendar());

				$timestamp = $this->formatters[$formatterId]->parse($value);

				$this->formatters[$formatterId]->setPattern($this->getPattern());

				$value = $this->formatters[$formatterId]->format($timestamp);

				if (intl_is_failure($this->formatters[$formatterId]->getErrorCode())) {
					throw new FilterException\InvalidArgumentException($this->formatters[$formatterId]->getErrorMessage());
				}
			} catch (IntlException $intlException) {
				throw new FilterException\InvalidArgumentException($intlException->getMessage(), 0, $intlException);
			}
		}

		return $value;
	}
}