class ButtonLoading
{
    start($ele)
    {
        const text = $ele.html();

        $ele.attr('data-text', text);

        $ele.addClass('btn_loading');
        $ele.prop('disabled', true);
        $ele.html('<i class="icon-load-c"></i>');
    }

    finish($ele)
    {
        console.log('finished');
        const text = $ele.attr('data-text');

        $ele.removeClass('btn_loading');
        $ele.prop('disabled', false);
        $ele.html(text);
    }

    disable($ele, text)
    {
        $ele.removeClass('btn_loading');
        $ele.prop('disabled', true);
        $ele.html(text);
    }
}

export default new ButtonLoading;
