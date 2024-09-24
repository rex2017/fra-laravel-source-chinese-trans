<?php
/**
 * 控制台可确定特性
 */

namespace Illuminate\Console;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
	 * 进行确认在继续操作之前
     *
     * This method only asks for confirmation in production.
     *
     * @param  string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = value($callback);

        if ($shouldConfirm) {
            if ($this->hasOption('force') && $this->option('force')) {
                return true;
            }

            $this->alert($warning);

            $confirmed = $this->confirm('Do you really wish to run this command?');

            if (! $confirmed) {
                $this->comment('Command Canceled!');

                return false;
            }
        }

        return true;
    }

    /**
     * Get the default confirmation callback.
	 * 得到默认的确认回调
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return $this->getLaravel()->environment() === 'production';
        };
    }
}
