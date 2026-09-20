import { reportByDate } from './date.js';
import { reportByCategory } from './category.js';
import { reportByCourseStatus } from './status.js';
import { reportByDuration } from './duration.js';
import { reportByParticipantStatus } from './participant-status.js';
import { reportByParticipantQuantity } from './participant-quantity.js';
import { reportByGender } from './gender.js';
import { reportByCourseMostScheduled } from './most-scheduled.js';
import { reportByCourseLeastScheduled } from './least-scheduled.js';
import { reportByParticipantAverage } from './participant-average.js';
import { reportByCanceled } from './canceled.js';
import { reportByCourseNotScheduled } from './not-scheduled.js';

// Registry mapping a report type key to its renderer.
export const renderers = {
    'date': reportByDate,
    'category': reportByCategory,
    'status': reportByCourseStatus,
    'duration': reportByDuration,
    'participant-by-status': reportByParticipantStatus,
    'participant-by-quantity': reportByParticipantQuantity,
    'gender': reportByGender,
    'most-scheduled': reportByCourseMostScheduled,
    'least-scheduled': reportByCourseLeastScheduled,
    'participant-average': reportByParticipantAverage,
    'canceled': reportByCanceled,
    'not-scheduled': reportByCourseNotScheduled,
};
