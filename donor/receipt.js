function downloadReceipt() {
    window.print();
}

function shareReceipt() {
    alert('Receipt link: ' + window.location.href);
}

if (window.location.search.indexOf('download=1') !== -1) {
    window.print();
}
