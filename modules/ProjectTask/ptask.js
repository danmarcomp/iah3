function showBookingDialog(form) {
    var today = new Date();
    var date_start = today.print('%Y-%m-%d');
    var hour_start = today.print('%H');
    var minute_start = today.print('%M');
    var related = {id: form.record.value, type: 'ProjectTask'};
    HoursEditView.showNew(null, null, null, date_start, hour_start, minute_start, 0, 30, related);
}
