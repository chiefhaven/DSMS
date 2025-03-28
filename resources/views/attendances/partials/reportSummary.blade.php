<form @submit.prevent>
    Report &nbsp;
    <select v-model="period" class="btn btn-light" id="period" name="period" @change="handlePeriodChange">
        <option value="">Choose date...</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="thisweek">This Week</option>
        <option value="lastweek">Last Week</option>
        <option value="thismonth">This Month</option>
        <option value="lastmonth">Last Month</option>
        <option value="thisyear">This Year</option>
        <option value="lastyear">Last Year</option>
        <option value="alltime">All Time</option>
        <option value="custom">Custom Date</option> <!-- Custom date option -->
    </select>
</form>

<!-- Bootstrap Modal for Custom Date Selection -->
<div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customDateModalLabel">Select Custom Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="start_date">From:</label>
                <input type="date" id="start_date" v-model="startDate" class="form-control" required>

                <label for="end_date" class="mt-2">To:</label>
                <input type="date" id="end_date" v-model="endDate" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button @click="downloadSummary('{{ $instructor->id }}')" class="btn btn-primary">
                    Download Report
                </button>
            </div>
        </div>
    </div>
</div>
