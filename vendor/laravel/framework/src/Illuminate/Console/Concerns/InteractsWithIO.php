<?php
/**
 * 控制台，与IO交互
 */

namespace Illuminate\Console\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

trait InteractsWithIO
{
    /**
     * The input interface implementation.
	 * 输入接口实现
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
	 * 输出接口实现
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * The default verbosity of output commands.
	 * 输出命令的默认长度
     *
     * @var int
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
	 * 人类可读的冗长级别和Symfony的OutputInterface之间的映射
     *
     * @var array
     */
    protected $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    /**
     * Determine if the given argument is present.
	 * 确定是否给定参数存在
     *
     * @param  string|int  $name
     * @return bool
     */
    public function hasArgument($name)
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Get the value of a command argument.
	 * 得到命令参数的值
     *
     * @param  string|null  $key
     * @return string|array|null
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get all of the arguments passed to the command.
	 * 得到传递给命令的所有参数
     *
     * @return array
     */
    public function arguments()
    {
        return $this->argument();
    }

    /**
     * Determine if the given option is present.
	 * 确定是否给定参数存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasOption($name)
    {
        return $this->input->hasOption($name);
    }

    /**
     * Get the value of a command option.
	 * 得到命令选项的值
     *
     * @param  string|null  $key
     * @return string|array|bool|null
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Get all of the options passed to the command.
	 * 得到传递给命令的所有选项
     *
     * @return array
     */
    public function options()
    {
        return $this->option();
    }

    /**
     * Confirm a question with the user.
	 * 确认问题与用户
     *
     * @param  string  $question
     * @param  bool  $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
	 * 提示用户输入
     *
     * @param  string  $question
     * @param  string|null  $default
     * @return mixed
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
	 * 提示用户输入并自动完成
     *
     * @param  string  $question
     * @param  array|callable  $choices
     * @param  string|null  $default
     * @return mixed
     */
    public function anticipate($question, $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
	 * 提示用户输入并自动完成
     *
     * @param  string  $question
     * @param  array|callable  $choices
     * @param  string|null  $default
     * @return mixed
     */
    public function askWithCompletion($question, $choices, $default = null)
    {
        $question = new Question($question, $default);

        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)
            : $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
	 * 提示用户输入，但在控制台中隐藏答案。
     *
     * @param  string  $question
     * @param  bool  $fallback
     * @return mixed
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);

        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
	 * 给用户一个选择从一组答案中
     *
     * @param  string  $question
     * @param  array  $choices
     * @param  string|null  $default
     * @param  mixed|null  $attempts
     * @param  bool|null  $multiple
     * @return string
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
	 * 将输入格式化为文本表
     *
     * @param  array  $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  string  $tableStyle
     * @param  array  $columnStyles
     * @return void
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        $table = new Table($this->output);

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }

    /**
     * Write a string as information output.
	 * 写一个字符串作为信息输出
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
	 * 写一个字符串作为标准输出
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
	 * 写一个字符串作为注释输出
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
	 * 写一个字符串作为问题输出
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
	 * 写一个字符串作为错误输出
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
	 * 写一个字符串作为警告输出
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        if (! $this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
	 * 写一个字符串作为提示输出
     *
     * @param  string  $string
     * @return void
     */
    public function alert($string)
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->comment(str_repeat('*', $length));
        $this->comment('*     '.$string.'     *');
        $this->comment(str_repeat('*', $length));

        $this->output->newLine();
    }

    /**
     * Set the input interface implementation.
	 * 设置输入接口实现
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return void
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Set the output interface implementation.
	 * 设置输出接口实现
     *
     * @param  \Illuminate\Console\OutputStyle  $output
     * @return void
     */
    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;
    }

    /**
     * Set the verbosity level.
	 * 设置冗长级别
     *
     * @param  string|int  $level
     * @return void
     */
    protected function setVerbosity($level)
    {
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
	 * 得到冗长级别根据Symfony的OutputInterface级别
     *
     * @param  string|int|null  $level
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Get the output implementation.
	 * 得到输出实现
     *
     * @return \Illuminate\Console\OutputStyle
     */
    public function getOutput()
    {
        return $this->output;
    }
}
