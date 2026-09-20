
<!-- Task details dialog -->
<div class="task-modal-backdrop" id="taskViewBackdrop" aria-hidden="true">
    <div class="task-modal" role="dialog" aria-modal="true" aria-labelledby="taskViewHeading">
        <button type="button" class="task-modal-close" data-task-close aria-label="Close"><i class="ti ti-x"></i></button>
        <h2 class="task-modal-title" id="taskViewHeading">View Task</h2>

        <div class="task-view-grid">
            <div class="task-view-item">
                <span class="task-view-label">Task Title</span>
                <strong class="task-view-title" id="viewTaskTitle"></strong>
            </div>

            <div class="task-view-item">
                <span class="task-view-label"><i class="ti ti-flag"></i> Priority</span>
                <span class="task-chip" id="viewTaskPriority"></span>
            </div>

            <div class="task-view-item task-view-wide">
                <span class="task-view-label">Description</span>
                <p class="task-view-description" id="viewTaskDescription"></p>
            </div>

            <div class="task-view-item">
                <span class="task-view-label"><i class="ti ti-calendar-event"></i> Due Date</span>
                <span class="task-view-value" id="viewTaskDue"></span>
            </div>

            <div class="task-view-item">
                <span class="task-view-label"><i class="ti ti-tag"></i> Status</span>
                <span class="task-chip" id="viewTaskStatus"></span>
            </div>

            <div class="task-view-item task-view-wide">
                <span class="task-view-label"><i class="ti ti-calendar-event"></i> Created at</span>
                <span class="task-view-value" id="viewTaskCreated"></span>
            </div>
        </div>

        <div class="task-modal-actions">
            <button type="button" class="task-btn task-btn-outline task-btn-delete" id="viewTaskDelete">Delete</button>
            <button type="button" class="task-btn task-btn-muted" data-task-close>Close</button>
        </div>
    </div>
</div>
