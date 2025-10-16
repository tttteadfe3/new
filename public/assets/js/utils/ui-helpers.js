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
    info: function(text) {
        this.show(text, 'info');
    }
};

const Confirm = {
    fire: (title, text) => {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '확인',
            cancelButtonText: '취소'
        });
    }
};
