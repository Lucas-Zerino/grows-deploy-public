#!/bin/bash

# Script para iniciar todos os workers em background

echo "🔧 Starting all workers..."

# Criar diretório de PIDs se não existir
mkdir -p tmp/pids

# Function to start a worker
start_worker() {
    local worker_name=$1
    local worker_file=$2
    local pid_file="tmp/pids/${worker_name}.pid"
    
    if [ -f "$pid_file" ]; then
        local old_pid=$(cat "$pid_file")
        if ps -p $old_pid > /dev/null 2>&1; then
            echo "⚠️  ${worker_name} is already running (PID: ${old_pid})"
            return
        fi
    fi
    
    nohup php "$worker_file" > "logs/${worker_name}.log" 2>&1 &
    local pid=$!
    echo $pid > "$pid_file"
    echo "✓ Started ${worker_name} (PID: ${pid})"
}

# Start workers
start_worker "message_sender" "workers/message_sender_worker.php"
start_worker "event_processor" "workers/event_processor_worker.php"
start_worker "outbox_processor" "workers/outbox_processor_worker.php"
start_worker "health_check" "workers/health_check_worker.php"

echo ""
echo "✅ All workers started!"
echo ""
echo "To view logs:"
echo "  tail -f logs/message_sender.log"
echo "  tail -f logs/event_processor.log"
echo "  tail -f logs/outbox_processor.log"
echo "  tail -f logs/health_check.log"
echo ""
echo "To stop workers:"
echo "  ./scripts/stop-workers.sh"
echo ""

