const Toast = {
    show: (text, type = 'info') => {
        let backgroundColor;
        let className;
        switch (type) {
            case 'success':
                className = "bg-success";
                break;
            case 'error':
                className = "bg-danger";
                break;
            case 'warning':
                className = "bg-warning";
                break;
            default:
                className = "bg-info";
                break;
        }

        Toastify({
            text: text,
            duration: 3000,
            close: true,
            gravity: "top",
            position: "center",
            stopOnFocus: true,
            className: className
        }).showToast();
    },
    success: function(text) {
        this.show(text, 'success');
    },
    error: function(text) {
        this.show(text, 'error');
    },
    warning: function(text) {
        this.show(text, 'warning');
    },
    info: function(text) {
        this.show(text, 'info');
    }
};

const Confirm = {
    fire: function(titleOrOptions, text = null) {
        let options = {};

        if (typeof titleOrOptions === 'string') {
            // 이전 방식 호출 핸들링: Confirm.fire('제목', '내용')
            options.title = titleOrOptions;
            if (text) {
                options.text = text;
            }
        } else if (typeof titleOrOptions === 'object' && titleOrOptions !== null) {
            // 새로운 방식 호출 핸들링: Confirm.fire({ title: '...', html: '...' })
            options = titleOrOptions;
        } else if (titleOrOptions) {
            // 인자가 하나만 있는 경우 핸들링: Confirm.fire('제목만')
            options.title = titleOrOptions;
        }

        const swalOptions = {
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '확인',
            cancelButtonText: '취소',
            ...options // 전달된 옵션들을 여기에 병합
        };

        return Swal.fire(swalOptions);
    }
};
