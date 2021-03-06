<?

	/**
	 * Represents a payment transaction update information.
	 * Objects of this class are returned by some methods of {@link Shop_PaymentMethod} class.
	 * @documentable
	 * @see http://lemonstand.com/docs/managing_payment_transactions Managing payment transactions
	 * @see Shop_PaymentMethod
	 * @package shop.models
	 * @author LemonStand eCommerce Inc.
	 */
	class Shop_TransactionUpdate
	{
		/**
		 * @var string Specifies the transaction code. 
		 * @documentable
		 */
		public $transaction_status_code;

		/**
		 * @var string Specifies the transaction status name.
		 * Transaction status name are specific for different payment gateways.
		 * @documentable
		 */
		public $transaction_status_name;

		/*
		 * @var string Additional, custom transaction data, can be used differently between payment methods
		 * @documentable
		 */
		public $data_1;


		/**
		 * @var string Specifies the transaction value.
		 * The value is the amount paid or refunded by the transaction
		 * @documentable
		 */
		public $transaction_value;

		/**
		 * @var int Settlement complete flag
		 * indicates if transaction has settled funds into an account
		 * @documentable
		 */
		public $transaction_complete;

		/**
		 * @var int Is refund flag
		 * indicates if transaction is for a refund
		 * @documentable
		 */
		public $transaction_refund;

		/**
		 * @var int Is void flag
		 * indicates if transaction should be ignored
		 * @documentable
		 */
		public $transaction_void;

		public function __construct($transaction_status_code=null, $transaction_status_name=null, $data_1 = null, $value = null, $complete = null, $refund = null, $void=null)
		{
			$this->transaction_status_code = $transaction_status_code;
			$this->transaction_status_name = $transaction_status_name;
			$this->transaction_value = $value;
			$this->transaction_complete = $complete;
			$this->transaction_refund = $refund;
			$this->transaction_void = $void;
			$this->data_1 = $data_1;
		}

		public function is_same_status($old_status){
			$relevant_fields = array(
				'transaction_status_code',
				'transaction_value',
				'transaction_complete',
				'transaction_refund',
				'transaction_void'
			);

			foreach($relevant_fields as $field){
				if($this->$field != $old_status->$field){
					return false;
				}
			}

			return true;
		}

	}

?>