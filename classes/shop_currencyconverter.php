<?

	class Shop_CurrencyConverter
	{
		public static $rate_cache = array();
		
		public static function create()
		{
			return new self();
		}

		/**
		 * Returns exchange rate for two currencies
		 * @param string $from_currency Specifies 3 character ISO currency code (e.g. USD) to convert from.
		 * @param string $to_currency Specifies a currency code to convert to
		 * @return number 
		 */
		public function get_rate($from_currency, $to_currency)
		{
			$from_currency = trim(strtoupper($from_currency));
			$to_currency = trim(strtoupper($to_currency));
			
			if ($from_currency == $to_currency)
				return 1;
			
			/*
			 * Look up in the cache
			 */

			$key = $from_currency.'_'.$to_currency;
			if (array_key_exists($key, self::$rate_cache))
				return self::$rate_cache[$key];
				
			/*
			 * Look up in the database cache
			 */

			$converter = Shop_CurrencyConversionParams::create()->get();
			if (!$converter)
				throw new Phpr_ApplicationException('Currency rate converter is not configured.');

			$interval = $converter->refresh_interval;

			if($converter->enable_cron_updates){
				/*
				* Get most recent rate, cron process keeps them up to date
				*/
				$record = $this->get_most_recent_record($from_currency, $to_currency);
				if($record){
					return self::$rate_cache[$key] = $record->rate;
				}
			} else {
				/*
				 * Look for a rate that has not expired the interval
				 */
				$record = Shop_CurrencyRateRecord::create();
				$record->where('from_currency=?', $from_currency);
				$record->where('to_currency=?',  $to_currency);
				$record->where('DATE_ADD(created_at, interval '.$interval.' hour) >= ?', Phpr_DateTime::now());
				$record = $record->find();

				if ($record)
					return self::$rate_cache[$key] = $record->rate;
			}


			/*
			 * Evaluate rate using a currency rate converter
			 */

			$class_name = $converter->class_name;
			if (!class_exists($class_name))
				throw new Phpr_ApplicationException("Currency converter class $class_name not found.");
				
			$converter->define_form_fields();

			$converter_obj = new $class_name();

			try {
				$rate = $converter_obj->get_exchange_rate($converter, $from_currency, $to_currency);
				$rate = $this->update_rate($from_currency,$to_currency,$rate);
				return self::$rate_cache[$key] = $rate;
			} catch (Exception $ex) {
				$record = $this->get_most_recent_record($from_currency, $to_currency);
				if (!$record)
					throw $ex;

				return self::$rate_cache[$key] = $record->rate;
			}
		}

		public function update_rate($from_currency, $to_currency, $rate){
			$result = Backend::$events->fireEvent('shop:onAdjustCurrencyConverterRate', $from_currency, $to_currency, $rate);
			foreach ($result as $new_rate) {
				if (is_numeric($new_rate)){
					$rate = $new_rate;
				}
			}
			$record = Shop_CurrencyRateRecord::create();
			$record->from_currency = $from_currency;
			$record->to_currency = $to_currency;
			$record->rate = $rate;
			$record->save();
			return $record->rate;
		}

		protected function get_most_recent_record($from_currency, $to_currency){
			$record = Shop_CurrencyRateRecord::create();
			$record->where('from_currency=?', $from_currency);
			$record->where('to_currency=?',  $to_currency);
			$record->order('created_at desc');
			$record = $record->find();
			return $record;
		}
		
		/**
		 * Converts currency value from one currency to another
		 * @param number $value Specifies a value to convert
		 * @param string $from_currency Specifies 3 character ISO currency code (e.g. USD) to convert from.
		 * @param string $to_currency Specifies a currency code to convert to
		 * @param int $round Number of decimal digits to round the result to. Pass NULL to disable rounding.
		 * @return number 
		 */
		public function convert($value, $from_currency, $to_currency, $round = 2)
		{
			$result = $value*$this->get_rate($from_currency, $to_currency);
			return $round === null ? $result : round($result, $round);
		}
		
		/**
		 * Converts currency value from specified currency to shop currency
		 * @param number $value Specifies a value to convert
		 * @param string $from_currency Specifies 3 character ISO currency code (e.g. USD) to convert from.
		 * @param int $round Number of decimal digits to round the result to. Pass NULL to disable rounding.
		 * @return number 
		 */
		public function convert_from($value, $from_currency, $round = 2)
		{
			$result = $value*$this->get_rate($from_currency, Shop_CurrencySettings::get()->code);
			return $round === null ? $result : round($result, $round);
		}

		public function update_all_rates($cron=false){
			$converter = Shop_CurrencyConversionParams::create()->get();
			if ( !$converter ) {
				return false;
			}
			if ( $cron  ) {
				if(!$converter->enable_cron_updates) {
					return false;
				}
			}
			$class_name = $converter->class_name;
			if ( !class_exists( $class_name ) ) {
				throw new Phpr_ApplicationException( "Currency converter class $class_name not found." );
			}

			$converter->define_form_fields();
			$converter_obj = new $class_name();
			$interval = $converter->refresh_interval ? $converter->refresh_interval : 24;
			$now = Phpr_DateTime::now()->toSqlDateTime();

			$sql = "SELECT from_currency, to_currency
					FROM shop_currency_exchange_rates
					GROUP BY from_currency, to_currency";

			$currencies = Db_DbHelper::queryArray($sql);

			if(!$currencies){
				return;
			}

			$sql_vars = array(
				'interval' =>$interval,
				'now' => Phpr_DateTime::now()->toSqlDateTime()
			);

			foreach($currencies as $data){
				$from_currency = $sql_vars['from_currency'] = $data['from_currency'];
				$to_currency = $sql_vars['to_currency']  = $data['to_currency'];

				$sql = "SELECT count(id)
						FROM shop_currency_exchange_rates
						WHERE from_currency = :from_currency
						AND to_currency = :to_currency
						AND DATE_ADD(created_at, interval :interval hour) >= :now";

				$result = Db_DbHelper::scalar($sql,$sql_vars);

				if(!$result) {
					try {
						$rate = $converter_obj->get_exchange_rate( $converter, $from_currency, $to_currency );
						$this->update_rate( $from_currency, $to_currency, $rate );
					} catch ( Exception $ex ) {
						traceLog( 'Failed to get exchange rate ' . $ex->getMessage() );
					}
				}
			}

			return true;
		}
	}

?>