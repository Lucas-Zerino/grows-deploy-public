#!/bin/bash

# Script para parar todos os workers

echo "🛑 Stopping all workers..."

# Function to stop a worker
stop_worker() {
    local worker_name=$1
    local pid_file="tmp/pids/${worker_name}.pid"
    
    if [ ! -f "$pid_file" ]; then
        echo "⚠️  ${worker_name} PID file not found"
        return
    fi
    
    local pid=$(cat "$pid_file")
    
    if ps -p $pid > /dev/null 2>&1; then
        kill $pid
        sleep 2
        
        if ps -p $pid > /dev/null 2>&1; then
            echo "⚠️  ${worker_name} did not stop gracefully, forcing..."
            kill -9 $pid
        fi
        
        echo "✓ Stopped ${worker_name} (PID: ${pid})"
    else
        echo "⚠️  ${worker_name} is not running"
    fi
    
    rm -f "$pid_file"
}

# Stop workers
stop_worker "message_sender"
stop_worker "event_processor"
stop_worker "outbox_processor"
stop_worker "health_check"

echo ""
echo "✅ All workers stopped!"
echo ""

