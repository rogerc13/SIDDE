import { $ } from "./globals.js";
export function participantStatusSelect(){ //participant status select dropdown
    $.ajax({
        type:'GET',
        url: '/reports/participant-status-select',
        dataType: 'json',
        success: function(response){
            // If server returns proper JSON, jQuery already gives an object here.
            // If it comes back as a string for some reason, fallback to parsing.
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            (response.statuses || []).forEach(status => {
                $("#participant_status").append(
                    `<option value="${status.id}">${status.name}</option>`
                );
            });
        },
        error: function(response){
            console.log(response);
        }
    });
};

