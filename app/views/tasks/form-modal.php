
<div class="task-modal-backdrop" id="taskFormBackdrop" aria-hidden="true">
    <div class="task-modal" role="dialog" aria-modal="true" aria-labelledby="taskFormTitle">
        <button type="button" class="task-modal-close" data-task-close aria-label="Close"><i class="ti ti-x"></i></button>
        <h2 class="task-modal-title" id="taskFormTitle">Create New Task</h2>

        <form id="taskForm" novalidate autocomplete="off">
            <div class="task-field">
                <label for="taskTitleInput">Task Title</label>
                <input id="taskTitleInput" name="title" type="text" maxlength="100" placeholder="What do you need to do?">
                <p class="task-field-error" data-error-for="title" role="alert"></p>
            </div>

            <div class="task-field">
                <label for="taskDescriptionInput">Description</label>
                <textarea id="taskDescriptionInput" name="description" rows="4" maxlength="5000" placeholder="Add more details about your task..."></textarea>
                <p class="task-field-error" data-error-for="description" role="alert"></p>
            </div>

            <div class="task-field-row">
                <div class="task-field">
                    <label for="taskDueInput">Due Date</label>
                    <input id="taskDueInput" name="due_date" type="date">
                    <p class="task-field-error" data-error-for="due_date" role="alert"></p>
                </div>
                <div class="task-field">
                    <label for="taskPriorityInput">Priority</label>
                    <select id="taskPriorityInput" name="priority">
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>

            <div class="task-field">
                <label for="taskStatusInput">Status</label>
                <select id="taskStatusInput" name="status">
                    <option value="todo" selected>To do</option>
                    <option value="in_progress">In progress</option>
                    <option value="done">Done</option>
                </select>
            </div>

            <p class="task-form-error" id="taskFormError" role="alert"></p>

            <div class="task-modal-actions">
                <button type="button" class="task-btn task-btn-outline" data-task-close>Cancel</button>
                <button type="submit" class="task-btn task-btn-primary" id="taskFormSubmit" disabled>Create Task</button>
            </div>
        </form>
    </div>
</div>
